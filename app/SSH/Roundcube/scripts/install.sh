sudo rm -rf __path__

if ! sudo wget https://github.com/roundcube/roundcubemail/releases/download/__version__/roundcubemail-__version__-complete.tar.gz; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo tar -xzf roundcubemail-__version__-complete.tar.gz; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf roundcubemail-__version__-complete.tar.gz; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf roundcubemail-__version__/installer; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mv roundcubemail-__version__ __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo touch __path__/database.sqlite; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo chmod 646 __path__/database.sqlite; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

config_content="<?php
\$config = [];
\$config['db_dsnw'] = 'sqlite:///__path__/database.sqlite?mode=0646';
\$config['imap_host'] = '__domain__:143';
\$config['smtp_host'] = '__domain__:25';
\$config['smtp_user'] = '%u';
\$config['smtp_pass'] = '%p';
\$config['support_url'] = '__support_url__';

// Name your service. This is displayed on the login screen and in the window title
\$config['product_name'] = 'Roundcube Webmail';
\$config['des_key'] = 'rcmail-!24ByteDESkey*Str';
\$config['plugins'] = [
    'archive',
    'zipdownload',
];

\$config['skin'] = 'elastic';
?>"

if ! sudo echo "$config_content" > __path__/config/config.inc.php; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Roundcube installed!"
