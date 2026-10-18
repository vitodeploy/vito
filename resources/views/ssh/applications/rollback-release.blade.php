set -e
file={!! escapeshellarg($component->envPath()) !!}
marker={!! escapeshellarg($component->sharedPath().'/.env-'.$deployment->id.'.prepared') !!}
if [ -f "$marker" ]; then
    if [ -f "$file.prev" ]; then
        cp -p -- "$file.prev" "$file.restore"
        mv -f -- "$file.restore" "$file"
    else
        rm -f -- "$file"
    fi
    @if ($previousPath !== null)
        ln -sfn -- {!! escapeshellarg($previousPath) !!} {!! escapeshellarg($component->currentPath()) !!}
    @else
        rm -f -- {!! escapeshellarg($component->currentPath()) !!}
    @endif
fi
