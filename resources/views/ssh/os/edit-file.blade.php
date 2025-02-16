@if($sudo) sudo @endif tee {!! $path !!} << 'VITO_SSH_EOF'
{!! $content !!}
VITO_SSH_EOF
