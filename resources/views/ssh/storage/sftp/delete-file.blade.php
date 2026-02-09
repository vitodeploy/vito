curl -k -u "{{ $username }}:{{ $password }}" sftp://{{ $host }}:{{ $port }}/ -Q "rm {{ $src }}"
