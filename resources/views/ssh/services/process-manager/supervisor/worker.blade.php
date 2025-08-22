[program:{{ $name }}]
process_name=%(program_name)s_%(process_num)02d
@if (!is_null($directory))
directory={{ $directory }}
@endif
command={{ $command }}
autostart={{ $autoStart }}
autorestart={{ $autoRestart }}
user={{ $user }}
numprocs={{ $numprocs }}
redirect_stderr=true
stdout_logfile={{ $logFile }}
stopwaitsecs=3600
stopasgroup=true
killasgroup=true
