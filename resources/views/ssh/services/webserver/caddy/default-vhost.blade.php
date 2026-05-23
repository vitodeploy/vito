:80, :443 {
    tls internal

    root * /var/www/vito-splash
    file_server

    header {
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        Referrer-Policy "strict-origin-when-cross-origin"
        Cache-Control "public, max-age=3600"
    }
}
