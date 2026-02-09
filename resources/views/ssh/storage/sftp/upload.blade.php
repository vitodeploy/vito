curl -k --ftp-create-dirs -T "{{ $src }}" -u "{{ $username }}:{{ $password }}" sftp://{{ $host }}:{{ $port }}/{{ $dest }}
