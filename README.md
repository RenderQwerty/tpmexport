Team password manager export
==========

This is source code of [docker image](https://hub.docker.com/r/jaels/tpmexport/) which acts as wrapper on the TPM-Keepass-Export script.
Main goal is to export projects and passwords from Team Password Manager instance to KeePass Password Safe XML 2.x files. It does so using the Team Password Manager API. After that you can import received XML to KeePass.

Inpired by https://teampasswordmanager.com/docs/keepass-export/

Usage
--------
* change $TPM_URL, $TPM_USERNAME and $TPM_PASSWORD in `docker-compose.yml` environments to aprropritate values for your instance
* Insert correct fqdn and ip address into `extra_hosts` section in `docker-compose.yml`
* run `docker-compose up`, wait untill its finished and grab your exported passwords from export/tpm_database.xml
