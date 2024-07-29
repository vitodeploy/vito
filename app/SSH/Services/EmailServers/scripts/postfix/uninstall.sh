sudo postfix stop

sudo DEBIAN_FRONTEND=noninteractive apt-get remove postfix -y

sudo rm -rf /etc/postfix

socket_path=$(sudo find /var/spool/postfix -type s -name "mux")

if [ -n "$socket_path" ]; then
    sudo rm -f "$socket_path"
fi

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoremove -y

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoclean -y
