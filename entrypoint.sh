#!/bin/sh

sed -i "s|YOUR_URL_OF_PASSWORD_MANAGER|$TPM_URL|g" /www/tpmke.php
sed -i "s/USER_WITH_API_ACCESS/$TPM_USERNAME/g" /www/tpmke.php
sed -i "s/PASSWORD_FOR_ABOVE_USER/$TPM_PASSWORD/g" /www/tpmke.php
php /www/tpmke.php
