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
@if (!empty($environment))
environment={!! collect($environment)->map(fn ($value, $key) => $key.'="'.$value.'"')->implode(',') !!}
@endif
redirect_stderr=true
stdout_logfile={{ $logFile }}
stopwaitsecs=3600
stopasgroup=true
killasgroup=true
