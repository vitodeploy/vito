set -e
@foreach ($paths as $path)
shared={!! escapeshellarg($component->sharedPath().'/'.$path) !!}
release={!! escapeshellarg($releasePath.'/'.$path) !!}
for target in "$shared" "$(dirname -- "$release")"; do
    while [ "$target" != {!! escapeshellarg($component->sharedPath()) !!} ] && [ "$target" != {!! escapeshellarg($releasePath) !!} ]; do
        if [ -L "$target" ]; then
            echo 'VITO_SSH_ERROR: shared paths cannot traverse symlinks' >&2
            exit 1
        fi
        target=$(dirname -- "$target")
    done
done
if [ ! -e "$shared" ]; then
    mkdir -p -- "$(dirname -- "$shared")"
    if [ -e "$release" ] && [ ! -L "$release" ]; then
        cp -a -- "$release" "$shared"
    else
        mkdir -p -- "$shared"
    fi
fi
mkdir -p -- "$(dirname -- "$release")"
rm -rf -- "$release"
ln -s -- "$shared" "$release"
@endforeach
rm -rf -- {!! escapeshellarg($releasePath.'/.env') !!}
ln -s -- {!! escapeshellarg($component->envPath()) !!} {!! escapeshellarg($releasePath.'/.env') !!}
