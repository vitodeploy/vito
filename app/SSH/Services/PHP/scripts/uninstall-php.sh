sudo service php__version__-fpm stop

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get remove -y php__version__ php__version__-fpm php__version__-mbstring php__version__-mysql php__version__-mcrypt php__version__-gd php__version__-xml php__version__-curl php__version__-gettext php__version__-zip php__version__-bcmath php__version__-soap php__version__-redis php__version__-sqlite3; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
