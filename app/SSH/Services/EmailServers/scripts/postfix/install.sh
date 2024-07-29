#!/bin/bash

# Actualizar la lista de paquetes e instalar las actualizaciones disponibles
sudo apt update && sudo apt upgrade -y

# Instalar Postfix
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix

# Seleccionar el tipo de configuración de Postfix
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
sudo debconf-set-selections <<< "postfix postfix/mailname string $(hostname --fqdn)"

# Reiniciar Postfix para aplicar la configuración
sudo systemctl restart postfix

# Instalar Dovecot
sudo apt install -y dovecot-core dovecot-imapd dovecot-pop3d dovecot-sieve dovecot-lmtpd

# Configuración básica de Dovecx1ot
#cat <<EOL | sudo tee /etc/dovecot/dovecot.conf
cat <<EOL | sudo tee sudo nano /etc/dovecot/conf.d/10-mail.conf
# Basic configuration

mail_location = maildir:/home/%u/Maildir

namespace inbox {
  inbox = yes
}

mail_privileged_group = mail

protocol !indexer-worker {
  # If folder vsize calculation requires opening more than this many mails from
  # disk (i.e. mail sizes aren't in cache already), return failure and finish
  # the calculation via indexer process. Disabled by default. This setting must
  # be 0 for indexer-worker processes.
  #mail_vsize_bg_after_count = 0
}
EOL

# Habilitar y reiniciar Dovecot
sudo systemctl enable dovecot
sudo systemctl restart dovecot

# Configuración de Postfix para usar Dovecot SASL
sudo postconf -e 'smtpd_sasl_type = dovecot'
sudo postconf -e 'smtpd_sasl_path = private/auth'
sudo postconf -e 'smtpd_sasl_auth_enable = yes'
sudo postconf -e 'smtpd_sasl_security_options = noanonymous'
sudo postconf -e 'smtpd_sasl_local_domain = $myhostname'
sudo postconf -e 'smtpd_recipient_restrictions = permit_sasl_authenticated,permit_mynetworks,reject_unauth_destination'

# Configurar Dovecot para Postfix SASL
sudo mkdir -p /etc/dovecot/sasl
cat <<EOL | sudo tee /etc/dovecot/conf.d/10-auth.conf
auth_mechanisms = plain login
!include auth-system.conf.ext
EOL

cat <<EOL | sudo tee /etc/dovecot/conf.d/10-master.conf
service auth {
  unix_listener /var/spool/postfix/private/auth {
    mode = 0660
    user = postfix
    group = postfix
  }
}
EOL

# Reiniciar Postfix y Dovecot para aplicar los cambios
sudo systemctl restart postfix
sudo systemctl restart dovecot

echo "Instalación y configuración de Postfix y Dovecot completada."
