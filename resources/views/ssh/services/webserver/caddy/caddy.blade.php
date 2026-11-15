{
        log {
                output file /var/log/caddy/errors.log {
                        roll_size 100MB
                    	roll_keep 10
                        roll_keep_for 720h
		}
                format json {
                        time_format iso8601
                }
		level ERROR
                exclude http.log.access
        }
}

(access_log) {
        log {
                output file /var/log/caddy/{args[0]}-access.log {
                        roll_size 100MB
                        roll_keep 10
                        roll_keep_for 720h
                }
                format json {
                        time_format iso8601
                }
        }
}

(security_headers) {
        header {
                -Server
                -X-Powered-By
                -Via

                Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
                X-Content-Type-Options "nosniff"
                X-Frame-Options "SAMEORIGIN"
                Referrer-Policy "strict-origin-when-cross-origin"
                X-XSS-Protection "1; mode=block"
                Content-Security-Policy "upgrade-insecure-requests"

                defer
        }
}

(compression) {
        encode {
                gzip 6
                zstd
                minimum_length 1024
        }
}

import sites-enabled/*
