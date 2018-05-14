<?php

// tpmke.php v.1.0
// http://teampasswordmanager.com/docs/keepass-export/
//
// Ferran Barba, June 2017
// info@teampasswordmanager.com
// http://teampasswordmanager.com
//
// PHP script that exports projects and passwords from Team Password Manager to a Keepass XML 2.x File
// using the TPM_Keepass_Export class (TPM_Keepass_Export.php)

// **************** PARAMETERS ****************

// File to export to (XML)
// If it exists it will be overwritten
$kfile = 'tpm_database.xml';

// URL (including index.php) of the installation of Team Password Manager
$tpm_url = 'https://YOUR_URL_OF_PASSWORD_MANAGER/index.php';

// Username and password
$tpm_username = 'USER_WITH_API_ACCESS';
$tpm_password = 'PASSWORD_FOR_ABOVE_USER';

// Initial project ID (0=root=everything)
// You can see the ID of the project in its URL: $tpm_url/prj/view/ID
$initial_project_id = 0;


// **************** GO ****************

// Load class file
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

$tki = new TPM_Keepass_Export();
$tki->set_file($kfile);
$tki->set_tpm_credentials($tpm_url, $tpm_username, $tpm_password);
$tki->set_initial_project($initial_project_id);
$tki->export();

