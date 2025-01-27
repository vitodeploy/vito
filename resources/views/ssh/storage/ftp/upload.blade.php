curl {{ $passive }} -T "{{ $src }}" -u "{{ $username }}:{{ $password }}" ftp{{ $ssl }}://{{ $host }}:{{ $port }}/{{ $dest }}
