mkdir -p -- {!! escapeshellarg($component->basePath().'/bin') !!}
@foreach ($executables as $name => $path)
test -x {!! escapeshellarg($path) !!}
ln -sfn -- {!! escapeshellarg($path) !!} {!! escapeshellarg($component->basePath().'/bin/'.$name) !!}
@endforeach
