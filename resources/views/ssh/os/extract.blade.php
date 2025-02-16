@php
    $extension = pathinfo($path, PATHINFO_EXTENSION);
@endphp

@if($extension === 'zip')
    unzip -o {{ $path }} -d {{ $destination }}
@elseif($extension === 'tar'))
    tar -xf {{ $path }} -C {{ $destination }}
@elseif(in_array($extension, ['gz', 'tar.gz']))
    tar -xzf {{ $path }} -C {{ $destination }}
@elseif(in_array($extension, ['bz2', 'tar.bz2']))
    tar -xjf {{ $path }} -C {{ $destination }}
@else
    echo "Unsupported archive format: {{ $extension }}"
@endif
