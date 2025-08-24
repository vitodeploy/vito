@php($sharedResources = data_get($site->type_data, 'modern_deployment_shared_resources', []))

cd {{ $site->basePath() }}/source
git stash
git clean -f
git pull origin {{ $site->branch }}

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        rm -rf {{ $releasePath }}/{{ $resource }}
        ln -s {{ $site->basePath() }}/source/{{ $resource }} {{ $releasePath }}/{{ $resource }}
        echo "{{ $resource }} linked"
    @endforeach
@endif

