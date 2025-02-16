sudo ufw --force disable

sudo cp /tmp/ufw.before.backup /etc/ufw/before.rules
sudo cp /tmp/ufw.after.backup /etc/ufw/after.rules
sudo cp /tmp/ufw.user.backup /etc/ufw/user.rules
sudo cp /tmp/ufw.before6.backup /etc/ufw/before6.rules
sudo cp /tmp/ufw.after6.backup /etc/ufw/after6.rules
sudo cp /tmp/ufw.user6.backup /etc/ufw/user6.rules

sudo ufw --force enable
