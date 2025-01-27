[program:{{ $name }}]
process_name=%(program_name)s_%(process_num)02d
command={{ $command }}
autostart={{ $autoStart }}
autorestart={{ $autoRestart }}
user={{ $user }}
numprocs={{ $numprocs }}
redirect_stderr=true
stdout_logfile={{ $logFile }}
stopwaitsecs=3600
