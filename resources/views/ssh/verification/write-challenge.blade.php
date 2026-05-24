set -e
mkdir -p {{ $dir }}
chmod 0755 {{ $dir }}
if [ -e {{ $file }} ]; then
    exit 17
fi
echo {{ $body }} | base64 -d > {{ $file }}.tmp
mv {{ $file }}.tmp {{ $file }}
chmod 0644 {{ $file }}
