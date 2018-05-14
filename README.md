Team password manager export
==========


Source code of docker image: https://hub.docker.com/r/jaels/tpmexport/

Inpired by https://teampasswordmanager.com/docs/keepass-export/

Usage
--------

Easy way:
* change $tpm_url, $tpm_username and $tpm_password in `source/tpmke.php` to aprropritate values for your instance
* Replace `hostname` with fqdn hostname of your tpm instance and `ipaddress` with ip address


Build without docker-compose:
* change $tpm_url, $tpm_username and $tpm_password in `source/tpmke.php` to aprropritate values for your instance
* Then run `docker build -t exporter .`
* You are ready to run builded image like this:
```bash
docker run --add-host="HOSTNAME:IP" --mount type=bind,src="$(pwd)"/export,dst=/export exporter:latest
```
> Dont forget to replace `HOSTNAME` with fqdn hostname of your tpm instance and `IP` with ip address.
