#!/bin/bash

path="/usr/share/i-librarian/www"

set -e

sudo apt-get purge i-librarian
sudo apt-get autoremove

# Delete old alias in alias.conf
aliasedited=$(grep -c "#I, Librarian" /etc/apache2/mods-available/alias.conf || true)

if [ -e /etc/apache2/mods-available/alias.conf -a $aliasedited = "2" ]
then
 sudo cp -v /etc/apache2/mods-available/alias.conf /etc/apache2/mods-available/alias.conf-original
 sed -e "/#I, Librarian start/,/#I, Librarian end/ d" </etc/apache2/mods-available/alias.conf-original >"$PWD/alias.conf"
 sudo mv -v "$PWD/alias.conf" /etc/apache2/mods-available/alias.conf
else
 echo 'alias.conf has been removed previously'
fi

if [ -e /etc/init.d/apache2 ]
then
 sudo /etc/init.d/apache2 restart
else
 echo 'Apache 2 uninstalled'
fi

echo 'I, Librarian uninstalled'

sleep 300

exit 0
