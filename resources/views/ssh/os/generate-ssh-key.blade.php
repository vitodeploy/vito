mkdir -p ~/.ssh
chmod 700 ~/.ssh
if [ ! -f ~/.ssh/{{ $name }} ]; then
    ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/{{ $name }}
fi
if [ ! -f ~/.ssh/{{ $name }}.pub ]; then
    ssh-keygen -y -f ~/.ssh/{{ $name }} > ~/.ssh/{{ $name }}.pub
fi
