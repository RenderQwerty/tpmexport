<?php

// TPM_Keepass_Export.php v.1.0 (Beta)
// http://teampasswordmanager.com/docs/keepass-export/
//
// Ferran Barba, June 2017
// info@teampasswordmanager.com
// http://teampasswordmanager.com
//
// PHP class to export projects and passwords to Keepass XML 2.x format

class TPM_Keepass_Export {

	// API version
	const API_VERSION = 4;

	// Keepass XML 2.x file to export
	protected $_file = '';

	// URL of the installation of Team Password Manager, including index.php
	// Example: http://mytpm.mydomain.com/index.php/
	protected $_tpm_base_url = '';

	// TPM credentials
	protected $_tpm_username = '';
	protected $_tpm_password = '';

	// Initial project (internal ID in TPM): the process will export this project and its childs (0=root - everything)
	protected $_initial_project = 0;

	// Handle for XML file
	protected $_writer = NULL;

	// Number of elements written to the XML file, to control flushing every 1000 elements, see _write_element()
	protected $_num_elements_written = 0;

	// Stack to keep the projects
	protected $_prj_stack = NULL;

	// To keep track of closing elements for projects
	protected $_prj_level = 1;

	// Create or not enclosing group ("Exported from Team Password Manager"). See _open_xml() and _close_xml()
	protected $_create_enclosing_group = TRUE;


	// **************** CONSTRUCTOR AND PARAMETERS ****************

	public function __construct ( $file = '', $tpm_base_url = '', $tpm_username = '', $tpm_password = '', $initial_project=0 ) {
		if ( $file ) {
			$this->set_file($file);
		}

		if ( $tpm_base_url && $tpm_username && $tpm_password ) {
			$this->set_tpm_credentials($tpm_base_url, $tpm_username, $tpm_password);
		}

		$this->set_initial_project($initial_project);
	}

	public function set_file ( $file ) {
		$this->_file = $file;
	}

	public function set_tpm_credentials ( $tpm_base_url, $tpm_username, $tpm_password ) {
		$this->_tpm_base_url = $tpm_base_url;
		if ( substr($this->_tpm_base_url, strlen($this->_tpm_base_url) - 1) != '/' ) {
			$this->_tpm_base_url .= '/';
		}

		$this->_tpm_username = $tpm_username;
		$this->_tpm_password = $tpm_password;
	}	

	public function set_initial_project ( $initial_project ) {
		$this->_initial_project = $initial_project;
	}

	// **************** EXPORT ****************

	public function export ( ) {
		// Init
		$this->_num_elements_written = 0;
		$this->_prj_level = 1;
		$this->_prj_stack = array();

		// Create file and first elements
		$this->_open_xml();

		// Initial project or root
		if ( $this->_initial_project == 0 ) {
			$this->_push_subprojects(0);
		} elseif ( $this->_initial_project > 0 ) {
			$this->_push_prj($this->_initial_project, $this->_prj_level);
		} else {
			$this->_tpm_die('Incorrect initial project');
		}

		// Main loop
		while ( $prj = $this->_pop_prj() ) {
			// Do we need to create closing group(s)?
			$project_id = $prj[0];
			$project_lvl = $prj[1];
			if ( $project_lvl < $this->_prj_level  ) {
				$num_groups_to_close =  $this->_prj_level - $project_lvl;
				$this->_prj_level = $project_lvl;
				for ( $i=1; $i<=$num_groups_to_close; $i++) {
					$this->_writer->endElement(); // Group
				} 
			}

			// Process the project
			$this->_process_project($project_id);
		} // main loop 

		// Close initial elements and final flush
		$this->_close_xml();
	} // export()

	// Exports a project and its passwords and pushes its subprojects into the stack
	protected function _process_project ( $project_id ) {
		$this->_prj_level++;

		// Start a Group (project). It will be closed in export() in the main loop
		$this->_writer->startElement('Group');
		
		// Get project data and export it
		$this->_export_project_data($project_id);

		// Export passwords of the project
		$this->_export_passwords_project($project_id);

		// Push subprojects into the stack
		$this->_push_subprojects($project_id);
	} // _process_project

	// Get project data and export it (name, notes, times)
	protected function _export_project_data ( $project_id ) {
		$rres = $this->_http_get('projects/' . $project_id . '.json');
		if ( $rres[0]==200 ) { // Ok
			$arr_prj = json_decode($rres[1], TRUE);

			$this->_write_element('Name', $arr_prj['name']);
			$this->_write_element('Notes', $arr_prj['notes']);

			$this->_writer->startElement('Times');
			$this->_write_element('CreationTime', str_replace('+00:00', 'Z', gmdate('c', strtotime($arr_prj['created_on']))));
			$this->_write_element('LastModificationTime', str_replace('+00:00', 'Z', gmdate('c', strtotime($arr_prj['updated_on']))));
			$this->_writer->endElement(); // Times
		}
	} // _export_project_data()

	// Export passwords of the project
	protected function _export_passwords_project ( $project_id ) {
		// Get the number of pages we have to request
		$num_pages = 0;
		$rres = $this->_http_get('projects/' . $project_id . '/passwords/count.json');
		if ( $rres[0]==200 ) { // Ok
			$arr_count = json_decode($rres[1], TRUE);
			$num_pages = $arr_count['num_pages'];
			// Get each page, and for each password, get each password
			for ( $i=1; $i<=$num_pages; $i++ ) {
				$rpwds = $this->_http_get('projects/' . $project_id . '/passwords/page/' . $i . '.json');
				if ( $rpwds[0]==200 ) { // Ok
					$arr_pwds = json_decode($rpwds[1], TRUE);
					foreach ( $arr_pwds as $pwd_data ) {
						$this->_export_password($pwd_data['id']);
					}
				}
			} // for each page
		} // get number of pages
	} // _export_passwords_project()

	// Export the password
	protected function _export_password ( $password_id ) {
		$rres = $this->_http_get('passwords/' . $password_id . '.json');
		if ( $rres[0]==200 ) { // Ok
			$arr_pwd = json_decode($rres[1], TRUE);
			if ( $arr_pwd ) {
				$this->_writer->startElement('Entry');

				// TPM => KeePass

				// tags => Tags (replacing , with ;)
				if ( $arr_pwd['tags'] ) {
					$tags_keepass = str_replace(',', ';', $arr_pwd['tags']);
					$this->_write_element('Tags', $tags_keepass);
				}

				// name => Title
				$this->_writer->startElement('String');
				$this->_write_element('Key', 'Title');
				$this->_write_element('Value', $arr_pwd['name']);
				$this->_writer->endElement(); // String

				// access_info => URL
				if ( $arr_pwd['access_info'] ) {
					$this->_writer->startElement('String');
					$this->_write_element('Key', 'URL');
					$this->_write_element('Value', $arr_pwd['access_info']);
					$this->_writer->endElement(); // String	
				}

				// username => UserName
				if ( $arr_pwd['username'] ) {
					$this->_writer->startElement('String');
					$this->_write_element('Key', 'UserName');
					$this->_write_element('Value', $arr_pwd['username']);
					$this->_writer->endElement(); // String
				}

				// email => Email (custom field in KeePass as it doesn't exist)
				// See the note in custom fields below
				if ( $arr_pwd['email'] ) {
					$this->_writer->startElement('String');
					$this->_write_element('Key', 'Email');
					$this->_write_element('Value', $arr_pwd['email']);
					$this->_writer->endElement(); // String
				}

				// password => Password
				if ( $arr_pwd['password'] ) {
					$this->_writer->startElement('String');
					$this->_write_element('Key', 'Password');
					$this->_write_element('Value', $arr_pwd['password'], 'ProtectInMemory', 'True');
					$this->_writer->endElement(); // String
				}

				// notes => Notes
				if ( $arr_pwd['notes'] ) {
					$this->_writer->startElement('String');
					$this->_write_element('Key', 'Notes');
					$this->_write_element('Value', $arr_pwd['notes']);
					$this->_writer->endElement(); // String
				}

				// Custom fields
				// Note: if a custom field is called "Email", it will overwrite the TPM email field
				for ( $cfi=1; $cfi<=10; $cfi++ ) {
					if ( $arr_pwd['custom_field' . $cfi] ) {
						$this->_writer->startElement('String');
						$this->_write_element('Key', $arr_pwd['custom_field' . $cfi]['label']);
						$this->_write_element('Value', $arr_pwd['custom_field' . $cfi]['data']);
						$this->_writer->endElement(); // String
					}	
				}
				
				// Times and expiry_date
				$this->_writer->startElement('Times');
				$this->_write_element('CreationTime', str_replace('+00:00', 'Z', gmdate('c', strtotime($arr_pwd['created_on']))));
				$this->_write_element('LastModificationTime', str_replace('+00:00', 'Z', gmdate('c', strtotime($arr_pwd['updated_on']))));
				if ( $arr_pwd['expiry_date'] ) {
					$this->_write_element('ExpiryTime', str_replace('+00:00', 'Z', gmdate('c', strtotime($arr_pwd['expiry_date']))));	
					$this->_write_element('Expires', 'True');
				} else {
					$this->_write_element('ExpiryTime', str_replace('+00:00', 'Z', gmdate('c', strtotime(date("Y-m-d H:i:s")))));	
					$this->_write_element('Expires', 'False');
				}
				$this->_writer->endElement(); // Times

				$this->_writer->endElement(); // Entry
			}
		}
	} // _export_password()

	// Pushes subrprojects into the stack, using the current level (_prj_level)
	// project_id = 0 means root
	protected function _push_subprojects ( $project_id ) {	
		$rres = $this->_http_get('projects/' . $project_id . '/subprojects.json');
		if ( $rres[0]==200 ) { // Ok
			$arr_prjs = json_decode($rres[1], TRUE);
			// Traverse in reverse to push into the stack (so that they're correctly popped)
			$arr_prjs_rev = array_reverse($arr_prjs);
			foreach ( $arr_prjs_rev as $subprj ) {
				$this->_push_prj($subprj['id'], $this->_prj_level);
			}
		}
	} // push_subprojects

	// **************** STACK TO MANAGE THE PROJECTS TREE ****************

	protected function _push_prj ( $project_id, $level ) {
		array_unshift($this->_prj_stack, array($project_id, $level));
	}

	protected function _pop_prj ( ) {
		return array_shift($this->_prj_stack);
	}

	protected function _debug_print_stack ( ) {
		echo 'Current stack:' . '<br/>';
		foreach ( $this->_prj_stack as $idx => $elem ) {
			print_r($elem);
			echo '<br/>';
		}
	}

	// **************** XML FILE ****************

	protected function _open_xml ( ) {
		if ( $this->_file ) {
			$this->_writer = new XMLWriter();
			if ( ! $this->_writer->openURI($this->_file) ) {
			    $this->_tpm_die('Failed to open XML file: ' . $this->_file);
			}
			
			$this->_writer->startDocument('1.0','UTF-8');  
			$this->_writer->setIndentString("\t");
			$this->_writer->setIndent(TRUE);   
			
			$this->_writer->startElement('KeePassFile');
			
			$this->_writer->startElement('Meta');
			$this->_write_element('Generator', 'Team Password Manager');
			$this->_writer->endElement(); // Meta

			$this->_writer->startElement('Root');

			if ( $this->_create_enclosing_group ) {
				$this->_writer->startElement('Group');
				$this->_write_element('Name', 'Exported from Team Password Manager');	
			}
		} else {
			$this->_tpm_die('No XML file.');
		}
	}

	protected function _write_element ( $name, $content, $attribute_name='', $attribute_value='' ) {
		if ( $attribute_name ) {
			$this->_writer->startElement($name);
			$this->_writer->writeAttribute($attribute_name, $attribute_value);
			$this->_writer->text($content);
			$this->_writer->endElement();
		} else {
			$this->_writer->writeElement($name, $content);	
		}
		// Flush every 1000 elements written
		$this->_num_elements_written++;
		if ( $this->_num_elements_written % 1000 == 0) {
			$this->_writer->flush();
		}
	}

	protected function _close_xml ( ) {
		if ( $this->_writer ) {
			if ( $this->_create_enclosing_group ) {
				$this->_writer->endElement(); // Group (Exported from Team Password Manager)
			}
			$this->_writer->endElement(); // Root
			$this->_writer->endElement(); // KeePassFile
			$this->_writer->endDocument();   
			$this->_writer->flush();
		}
	}	

	// **************** API ****************

	protected function _http_get ( $request_uri ) {
		// Construct headers
		$headers = array(
			'Content-Type: application/json; charset=utf-8',
			'Expect:' // workaround to the 100-continue header
		);

		// Make request
		$ch = curl_init($this->_tpm_base_url . 'api/v' . self::API_VERSION . '/' . $request_uri);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE); // Includes the header in the output
		curl_setopt($ch, CURLOPT_USERPWD, $this->_tpm_username . ":" . $this->_tpm_password); // HTTP Basic Authentication

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);	
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		// Separate response headers and body
		list($headers, $body) = explode("\r\n\r\n", $result, 2);
		return array($status, $body);
	} // http_get()

	// **************** OTHER ****************

	private function _tpm_die ( $strm ) {
		// Destroy projects stack
		unset($this->_prj_stack);

		// Delete file
		if ( $this->_writer ) {
			$this->_close_xml();
			unlink($this->_file);
		}
		die($strm);
	} 
	
} // class TPM_Keepass_Export
