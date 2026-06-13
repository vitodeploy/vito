# Gracefully stop and drop every PostgreSQL cluster before removing packages
if command -v pg_lsclusters >/dev/null 2>&1; then
    pg_lsclusters -h 2>/dev/null | while read -r cluster_version cluster_name _; do
        sudo pg_dropcluster --stop "$cluster_version" "$cluster_name" 2>/dev/null || true
    done
fi

sudo systemctl stop postgresql 2>/dev/null || true
sudo service postgresql stop 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge -y postgresql-* 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean -y 2>/dev/null || true

# Remove repository and keys
sudo rm -f /etc/apt/sources.list.d/pgdg.list
sudo rm -f /usr/share/keyrings/postgresql-archive-keyring.gpg
sudo apt-key del ACCC4CF8 2>/dev/null || true

sudo rm -rf /etc/postgresql
sudo rm -rf /var/lib/postgresql
sudo rm -rf /var/log/postgresql
sudo rm -rf /var/run/postgresql
