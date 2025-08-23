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

ln -s {{ $site->basePath() }}/releases/initial {{ $site->basePath() }}/current

composer install --no-dev --working-dir={{ $site->basePath() }}/current --prefer-dist --optimize-autoloader

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        rm -rf {{ $site->basePath() }}/releases/initial/{{ $resource }}
        ln -s {{ $site->basePath() }}/source/{{ $resource }} {{ $site->basePath() }}/releases/initial/{{ $resource }}
        echo "{{ $resource }} linked"
    @endforeach
@endif
