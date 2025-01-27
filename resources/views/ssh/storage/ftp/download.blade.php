curl {{ $passive }} -u "{{ $username }}:{{ $password }}" ftp{{ $ssl }}://{{ $host }}:{{ $port }}/{{ $src }} -o "{{ $dest }}"
