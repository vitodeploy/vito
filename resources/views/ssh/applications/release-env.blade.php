set -e
umask 077
file={!! escapeshellarg($component->envPath()) !!}
marker={!! escapeshellarg($component->sharedPath().'/.env-'.$deployment->id.'.prepared') !!}
if [ ! -f "$marker" ]; then
    if [ -f "$file" ]; then
        cp -p -- "$file" "$file.prev"
    else
        rm -f -- "$file.prev"
    fi
    touch -- "$marker"
fi
if [ -f "$file.next" ]; then
    mv -f -- "$file.next" "$file"
else
    test -f "$file"
fi
