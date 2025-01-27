export DEBIAN_FRONTEND=noninteractive
echo "{{ $key }}" | sudo tee -a /root/.ssh/authorized_keys
sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}
sudo usermod -aG sudo {{ $user }}
echo "{{ $user }} ALL=(ALL) NOPASSWD:ALL" | sudo tee -a /etc/sudoers
sudo mkdir /home/{{ $user }}
sudo mkdir /home/{{ $user }}/.ssh
echo "{{ $key }}" | sudo tee -a /home/{{ $user }}/.ssh/authorized_keys
sudo chown -R {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chsh -s /bin/bash {{ $user }}
sudo su - {{ $user }} -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<< y
