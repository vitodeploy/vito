export DEBIAN_FRONTEND=noninteractive
if ! sudo useradd -p $(openssl passwd -1 __password__) __user__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo mkdir /home/__user__
sudo mkdir /home/__user__/.logs
sudo mkdir /home/__user__/tmp
sudo mkdir /home/__user__/bin
sudo mkdir /home/__user__/.ssh
echo 'export PATH="/home/__user__/bin:$PATH"' | sudo tee -a /home/__user__/.bashrc
sudo usermod -a -G __user__ __server_user__
sudo chown -R __user__:__user__ /home/__user__
sudo chmod -R 755 /home/__user__
sudo chmod -R 700 /home/__user__/.ssh
sudo chsh -s /bin/bash __user__
echo "Created user __user__."
