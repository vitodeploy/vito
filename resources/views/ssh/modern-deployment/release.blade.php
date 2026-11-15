ln -sfn -- {!! escapeshellarg($releasePath) !!} {!! escapeshellarg($basePath.'/current') !!}

printf '%s\n' {!! escapeshellarg('Version '.basename($releasePath).' activated! 🎉') !!}
