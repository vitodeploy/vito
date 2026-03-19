sudo kill $(cat {!! $basePath !!}/certbot.pid 2>/dev/null) 2>/dev/null || true
sudo rm -f {!! $basePath !!}/auth-signal-* {!! $basePath !!}/auth-proceed-* {!! $basePath !!}/cleanup-signal-* {!! $basePath !!}/cleanup-done-* {!! $basePath !!}/certbot.log {!! $basePath !!}/certbot.pid {!! $basePath !!}/auth-hook.sh {!! $basePath !!}/cleanup-hook.sh 2>/dev/null || true
