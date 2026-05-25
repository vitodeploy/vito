export DEBIAN_FRONTEND=noninteractive

mkdir -p {{ $path }}

chmod -R 755 {{ $path }}
