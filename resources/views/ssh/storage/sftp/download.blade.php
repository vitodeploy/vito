curl -k -u "{{ $username }}:{{ $password }}" sftp://{{ $host }}:{{ $port }}/{{ $src }} -o "{{ $dest }}"
