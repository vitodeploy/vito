export DEBIAN_FRONTEND=noninteractive
echo "__key__" | sudo tee -a /home/root/.ssh/authorized_keys
sudo useradd -p $(openssl passwd -1 __password__) __user__
sudo usermod -aG sudo __user__
echo "__user__ ALL=(ALL) NOPASSWD:ALL" | sudo tee -a /etc/sudoers
sudo mkdir /home/__user__
sudo mkdir /home/__user__/.ssh
echo "__key__" | sudo tee -a /home/__user__/.ssh/authorized_keys
sudo chown -R __user__:__user__ /home/__user__
sudo chsh -s /bin/bash __user__
sudo su - __user__ -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<< y
