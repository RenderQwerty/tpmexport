# Source code of docker image: jaels/tpmexport

Inpired by https://teampasswordmanager.com/docs/keepass-export/

# HOW TO BUILD: clone this repo, insert appropriate values (URL,LOGIN,PASSWORD) to source/tpmke.php

Then run `docker build .`

Run this image like this: `docker run --add-host="tpmhostNAME:tpmIP" --mount type=bind,src="$(pwd)"/export,dst=/export`

