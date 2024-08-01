#!/bin/bash

# Actualizar la lista de paquetes e instalar las actualizaciones disponibles
sudo apt update && sudo apt upgrade -y

# Instalar Postfix
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix

# Instalar OpenDKIM
sudo apt-get install -y opendkim opendkim-tools

# Asegúrate de que 'vito' esté en el grupo 'dovecot'
sudo usermod -aG dovecot vito
sudo usermod -aG postfix vito
sudo usermod -aG dovecot postfix
sudo usermod -aG postfix dovecot

# Configurar OpenDKIM
sudo mkdir -p /etc/opendkim/keys/__domain__
sudo opendkim-genkey -t -s default -d __domain__
sudo mv default.private /etc/opendkim/keys/__domain__/default.private
sudo mv default.txt /etc/opendkim/keys/__domain__/default.txt

sudo chown -R opendkim:opendkim /etc/opendkim/keys
sudo chmod 0775 /etc/opendkim/keys/__domain__/default.private

# Configurar OpenDKIM en Postfix
sudo cat <<EOL | sudo tee -a /etc/opendkim.conf
AutoRestart             Yes
AutoRestartRate         10/1h
UMask                   002
Syslog                  Yes
SyslogSuccess           Yes
LogWhy                  Yes
Canonicalization        relaxed/simple
ExternalIgnoreList      refile:/etc/opendkim/trusted.hosts
InternalHosts           refile:/etc/opendkim/trusted.hosts
KeyTable                refile:/etc/opendkim/key.table
SigningTable            refile:/etc/opendkim/signing.table
Mode                    sv
PidFile                 /var/run/opendkim/opendkim.pid
SignatureAlgorithm      rsa-sha256

Socket                  inet:12345@localhost
EOL

sudo cat <<EOL | sudo tee /etc/opendkim/trusted.hosts
127.0.0.1
localhost
__domain__
EOL

sudo cat <<EOL | sudo tee /etc/opendkim/key.table
default._domainkey.__domain__ __domain__:default:/etc/opendkim/keys/__domain__/default.private
EOL

sudo cat <<EOL | sudo tee /etc/opendkim/signing.table
*@__domain__ default._domainkey.__domain__
EOL

# Seleccionar el tipo de configuración de Postfix
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
sudo debconf-set-selections <<< "postfix postfix/mailname string __domain__"

# Configurar Postfix
sudo postconf -e 'myhostname = __domain__'
sudo postconf -e 'mydestination = localhost'
sudo postconf -e 'virtual_mailbox_domains = __domain__'
sudo postconf -e 'virtual_mailbox_base = /var/mail/vhosts'
sudo postconf -e 'virtual_mailbox_maps = hash:/etc/postfix/vmailbox'
sudo postconf -e 'virtual_minimum_uid = 100'
sudo postconf -e 'virtual_uid_maps = static:5000'
sudo postconf -e 'virtual_gid_maps = static:5000'
sudo postconf -e 'virtual_transport = virtual'
#sudo postconf -e 'virtual_alias_domains = __domain__'
sudo postconf -e 'virtual_alias_maps = hash:/etc/postfix/virtual'
sudo postconf -e 'smtpd_sasl_type = dovecot'
sudo postconf -e 'smtpd_sasl_path = private/auth'
sudo postconf -e 'smtpd_sasl_auth_enable = yes'
sudo postconf -e 'smtpd_tls_security_level = may'
sudo postconf -e 'inet_protocols = ipv4'
sudo postconf -e 'smtpd_sasl_security_options = noanonymous'
sudo postconf -e 'smtpd_sasl_local_domain = __domain__'
sudo postconf -e 'smtpd_recipient_restrictions = permit_sasl_authenticated,permit_mynetworks,reject_unauth_destination'

# Configurar Postfix para OpenDKIM
sudo postconf -e 'milter_protocol = 2'
sudo postconf -e 'milter_default_action = accept'
sudo postconf -e 'smtpd_milters = inet:localhost:12345'
sudo postconf -e 'non_smtpd_milters = inet:localhost:12345'


sudo touch /etc/postfix/vmailbox
echo "usuario@rdump.dev rdump.dev/usuario" | sudo tee -a /etc/postfix/vmailbox
sudo postmap /etc/postfix/vmailbox
sudo chmod 0775 /etc/postfix/vmailbox
sudo chmod 0775 /etc/postfix/vmailbox.db
sudo chown postfix:postfix /etc/postfix/vmailbox
sudo chown postfix:postfix /etc/postfix/vmailbox.db


sudo touch /etc/postfix/virtual
echo "usuario@rdump.dev usuario@rdump.dev" | sudo tee -a /etc/postfix/virtual
sudo postmap /etc/postfix/virtual
sudo chmod 0775 /etc/postfix/virtual
sudo chmod 0775 /etc/postfix/virtual.db
sudo chown postfix:postfix /etc/postfix/virtual
sudo chown postfix:postfix /etc/postfix/virtual.db


# Reiniciar Postfix para aplicar la configuración
sudo systemctl restart postfix


# Instalar Dovecot
sudo apt install -y dovecot-core dovecot-imapd dovecot-pop3d dovecot-sieve dovecot-lmtpd

# Configuración básica de Dovecot
cat <<EOL | sudo tee /etc/dovecot/conf.d/10-mail.conf
# Basic configuration
mail_location = maildir:/var/mail/vhosts/%d/%n
namespace inbox {
  inbox = yes
}
mail_privileged_group = mail
protocol !indexer-worker {
}
EOL

sudo cat <<EOL | sudo tee /etc/dovecot/conf.d/10-auth.conf
auth_mechanisms = plain login
!include auth-passwdfile.conf.ext
!include auth-static.conf.ext
EOL

sudo cat <<EOL | sudo tee /etc/dovecot/conf.d/auth-passwdfile.conf.ext
passdb {
  driver = passwd-file
  args = scheme=SHA1 /etc/dovecot/users
}
userdb {
  driver = static
  args = uid=vito gid=vito home=/var/mail/vhosts/%d/%n
}
EOL


# Crear directorios y establecer permisos
sudo mkdir -p /var/mail/vhosts/__domain__
sudo chown -R dovecot:dovecot /var/mail/vhosts
sudo mkdir -p /var/mail/vhosts/__domain__
sudo chmod -R 0775 /var/mail/vhosts

# Asegurarse de que el archivo de usuarios tenga permisos correctos
sudo touch /etc/dovecot/users
sudo chown dovecot:dovecot /etc/dovecot/users
sudo chmod 0777 /etc/dovecot/users

# Añadir usuario de ejemplo
echo "usuario@rdump.dev:$(sudo doveadm pw -s SHA1 -p password)" | sudo tee -a /etc/dovecot/users

# Configurar Dovecot para Postfix SASL
sudo cat <<EOL | sudo tee /etc/dovecot/conf.d/10-master.conf
service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0660
    user = postfix
    group = postfix
  }
}
EOL

# Reiniciar Postfix y Dovecot para aplicar los cambios
sudo systemctl enable dovecot
sudo systemctl restart dovecot
sudo systemctl restart postfix

sudo echo "Instalación y configuración de Postfix y Dovecot con usuarios virtuales completada."
