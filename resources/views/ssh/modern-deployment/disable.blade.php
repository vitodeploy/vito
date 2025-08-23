@php($basePath = str_replace('/current', '', $site->path))
@php($tmpPath = $basePath . '-tmp')

LAST_RELEASE_PATH=$(readlink {{ $site->path }})

mv $LAST_RELEASE_PATH {{ $tmpPath }}

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        rm -rf {{ $tmpPath }}/{{ $resource }}
        mv {{ $site->path }}/source/{{ $resource }} {{ $tmpPath }}/{{ $resource }}
    @endforeach
@endif

rm -rf {{ $basePath }}
mv {{ $tmpPath }} {{ $basePath }}
