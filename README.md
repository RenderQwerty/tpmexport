Team password manager export
==========

Source code of docker image: https://hub.docker.com/r/jaels/tpmexport/

Inpired by https://teampasswordmanager.com/docs/keepass-export/

Usage
--------

How to run with docker-compose:
* change $TPM_URL, $TPM_USERNAME and $TPM_PASSWORD in `docker-compose.yml` environments to aprropritate values for your instance
* Insert correct fqdn and ip address into `extra_hosts` section in `docker-compose.yml`
* run `docker-compose up`, wait untill its finished and grab your exported passwords from export/tpm_database.xml

Run with docker:
* Build: `docker build -t exporter .`
* Run builded image like this:
```bash
docker run --add-host="HOSTNAME:IP" -e "TPM_URL=https://tpm.example.com/index.php" -e "TPM_USERNAME=admin"-e "TPM_PASSWORD=strongpassword" --mount type=bind,src="$(pwd)"/export,dst=/export exporter:latest
```
> Dont forget to replace `HOSTNAME` with fqdn hostname of your tpm instance and `IP` with ip address.
