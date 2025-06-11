#[laravel-octane-map]
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
#[/laravel-octane-map]
