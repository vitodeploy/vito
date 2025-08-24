@php($tmpPath = $site->basePath() . '-tmp')

mv {{ $site->basePath() }} {{ $tmpPath }}

mkdir -p {{ $site->basePath() }}/releases

mv {{ $tmpPath }} {{ $site->basePath() }}/source

@include('ssh.git.clone',[
  'host' => str($site->getFullRepositoryUrl())->after('@')->before('-'),
  'repo' => $site->getFullRepositoryUrl(),
  'path' => $site->basePath() . '/releases/initial',
  'branch' => $site->branch,
  'key' => $site->getSshKeyName(),
])

# move vendor to initial release if exists
if [ -d "{{ $site->basePath() }}/source/vendor" ]; then
    mv {{ $site->basePath() }}/source/vendor {{ $site->basePath() }}/initial/vendor
fi

# move node_modules to initial release if exists
if [ -d "{{ $site->basePath() }}/source/node_modules" ]; then
    mv {{ $site->basePath() }}/source/node_modules {{ $site->basePath() }}/initial/node_modules
fi

ln -s {{ $site->basePath() }}/releases/initial {{ $site->basePath() }}/current

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        rm -rf {{ $site->basePath() }}/releases/initial/{{ $resource }}
        ln -s {{ $site->basePath() }}/source/{{ $resource }} {{ $site->basePath() }}/releases/initial/{{ $resource }}
        echo "{{ $resource }} linked"
    @endforeach
@endif
