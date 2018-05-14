## Source code of docker image: https://hub.docker.com/r/jaels/tpmexport/

Inpired by https://teampasswordmanager.com/docs/keepass-export/

#### How to build:
```
clone this repo, insert appropriate values (URL,LOGIN,PASSWORD) to source/tpmke.php
```
Then run `docker build .`

And you are ready to run builded image like this:
```bash
docker run --add-host="hostname:ipaddress" --mount type=bind,src="$(pwd)"/export,dst=/export
```

