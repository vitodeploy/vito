sudo DEBIAN_FRONTEND=noninteractive apt-get purge goaccess -y

sudo rm -rf /var/lib/goaccess

sudo rm -f /etc/apt/sources.list.d/goaccess.list /usr/share/keyrings/goaccess.gpg
