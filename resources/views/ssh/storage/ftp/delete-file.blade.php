curl {{ $passive }} -u "{{ $username }}:{{ $password }}" ftp{{ $ssl }}://{{ $host }}:{{ $port }}/{{ $src }} -Q "DELE /{{ $src }}"
