#!/bin/sh
# install phpredis extension.

set -e


git clone --depth=50 https://github.com/nicolasff/phpredis
cd phpredis 

major_version=`php -v | head -n 1 | cut -c 5`
if [ $major_version == "7" ]
then
  git checkout php7
fi

exit;
phpize
./configure
make
sudo make install

echo "extension=redis.so" > redis.ini
phpenv config-add redis.ini

