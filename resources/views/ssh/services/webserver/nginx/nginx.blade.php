user {{ $user }};
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
	worker_connections 768;
}

http {


	sendfile on;
	tcp_nopush on;
	tcp_nodelay on;
	keepalive_timeout 65;
	types_hash_max_size 2048;
	server_tokens off;


	include /etc/nginx/mime.types;
	default_type application/octet-stream;


	ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
	ssl_prefer_server_ciphers on;


	access_log /var/log/nginx/access.log;
	error_log /var/log/nginx/error.log;


	gzip on;



	include /etc/nginx/conf.d/*.conf;
	include /etc/nginx/sites-enabled/*;
}
