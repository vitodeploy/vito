export DEBIAN_FRONTEND=noninteractive
@if($clearKeys)
# Clear all existing keys and add only the new unique key (for custom servers)
echo "{{ $key }}" | sudo tee /root/.ssh/authorized_keys
@else
echo "{{ $key }}" | sudo tee -a /root/.ssh/authorized_keys
@endif
sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}
sudo usermod -aG sudo {{ $user }}
echo "{{ $user }} ALL=(ALL) NOPASSWD:ALL" | sudo tee -a /etc/sudoers
sudo mkdir /home/{{ $user }}
sudo mkdir /home/{{ $user }}/.ssh
@if($clearKeys)
# Clear all existing keys for the new user and add only the new unique key
echo "{{ $key }}" | sudo tee /home/{{ $user }}/.ssh/authorized_keys
@else
echo "{{ $key }}" | sudo tee -a /home/{{ $user }}/.ssh/authorized_keys
@endif
sudo chown -R {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chsh -s /bin/bash {{ $user }}
sudo su - {{ $user }} -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<< y
