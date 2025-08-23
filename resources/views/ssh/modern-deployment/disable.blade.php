@php($tmpPath = $site->basePath() . '-tmp')

LAST_RELEASE_PATH=$(readlink {{ $site->path }})

mv $LAST_RELEASE_PATH {{ $tmpPath }}

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        unlink {{ $tmpPath }}/{{ $resource }}
        mv {{ $site->basePath() }}/source/{{ $resource }} {{ $tmpPath }}/{{ $resource }}
    @endforeach
@endif

rm -rf {{ $site->basePath() }}
mv {{ $tmpPath }} {{ $site->basePath() }}
rm -rf {{ $site->basePath() }}/releases
rm -rf {{ $site->basePath() }}/source
rm -rf {{ $site->basePath() }}/current

cd {{ $site->basePath() }}
git stash
git clean -f
