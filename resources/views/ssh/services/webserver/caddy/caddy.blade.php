# Common snippets
(security_headers) {
	header {
			# Remove server and software information
			-Server
			-X-Powered-By
			-Via

			# Security headers
			Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
			X-Content-Type-Options "nosniff"
			X-Frame-Options "SAMEORIGIN"
			Referrer-Policy "strict-origin-when-cross-origin"
			X-XSS-Protection "1; mode=block"
			Content-Security-Policy "upgrade-insecure-requests"

			# Enable compression
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