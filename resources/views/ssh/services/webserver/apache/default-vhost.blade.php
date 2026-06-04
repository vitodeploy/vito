<VirtualHost *:80>
    DocumentRoot /var/www/vito-splash
    DirectoryIndex index.html

    <Directory /var/www/vito-splash>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Cache-Control "public, max-age=3600"
</VirtualHost>
