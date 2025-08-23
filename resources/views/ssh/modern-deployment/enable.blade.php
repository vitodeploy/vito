@php($tmpPath = $site->path . '-tmp')

mv {{ $site->path }} {{ $tmpPath }}

mkdir -p {{ $site->path }}/releases

mv {{ $tmpPath }} {{ $site->path }}/source

@include('ssh.git.clone',[
  'host' => str($site->getFullRepositoryUrl())->after('@')->before('-'),
  'repo' => $site->getFullRepositoryUrl(),
  'path' => $site->path . '/releases/initial',
  'branch' => $site->branch,
  'key' => $site->getSshKeyName(),
])

ln -s {{ $site->path }}/releases/initial {{ $site->path }}/current

composer install --no-dev --working-dir={{ $site->path }}/current --prefer-dist --optimize-autoloader

@if (count($sharedResources) > 0)
    @foreach ($sharedResources as $resource)
        rm -rf {{ $site->path }}/releases/initial/{{ $resource }}
        ln -s {{ $site->path }}/source/{{ $resource }} {{ $site->path }}/releases/initial/{{ $resource }}
        echo "{{ $resource }} linked"
    @endforeach
@endif
