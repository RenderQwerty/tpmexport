Team password manager export
==========


Source code of docker image: https://hub.docker.com/r/jaels/tpmexport/

Inpired by https://teampasswordmanager.com/docs/keepass-export/

Usage
--------

Easy way:
* change $tpm_url, $tpm_username and $tpm_password in `source/tpmke.php` to aprropritate values for your instance
* Replace `hostname` with fqdn hostname of your tpm instance and `ipaddress` with ip address

#### How to build:
```
clone this repo, insert appropriate values (URL,LOGIN,PASSWORD) to source/tpmke.php
```
Then run `docker build -t exporter .`

And you are ready to run builded image like this:
```bash
docker run --add-host="HOSTNAME:IP" --mount type=bind,src="$(pwd)"/export,dst=/export exporter:latest
```
