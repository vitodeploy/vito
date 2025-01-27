export DEBIAN_FRONTEND=noninteractive

rm -rf {{ $path }}

mkdir {{ $path }}

chmod -R 755 {{ $path }}
