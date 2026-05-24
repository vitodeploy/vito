:80, :443 {
    import access_log default
    import compression
    import security_headers

    tls internal

    root * /var/www/vito-splash
    file_server

    header {
        Cache-Control "public, max-age=3600"
    }
}
