@if($sudo) sudo @endif tee {!! $path !!} << 'VITO_SSH_EOF' > /dev/null
{!! $content !!}
VITO_SSH_EOF

if [ $? -eq 0 ]; then
    echo "Successfully wrote to {{ $path }}"
else
    echo 'VITO_SSH_ERROR' && exit 1
fi
