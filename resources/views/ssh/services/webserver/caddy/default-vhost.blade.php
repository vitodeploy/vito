:80 {
    import access_log default
    import compression
    import security_headers

    handle_path /.well-known/vito/* {
        root * /var/lib/vito/verify
        header Cache-Control "no-store"
        file_server
    }

    handle {
        root * /var/www/vito-splash
        header Cache-Control "public, max-age=3600"
        file_server
    }
}
