#!/bin/bash

# Desactivar y detener los servicios
sudo systemctl disable postfix
sudo systemctl stop postfix

sudo systemctl disable dovecot
sudo systemctl stop dovecot

sudo systemctl disable opendkim
sudo systemctl stop opendkim

# Eliminar los paquetes de Postfix, Dovecot y OpenDKIM
sudo DEBIAN_FRONTEND=noninteractive apt purge -y postfix dovecot-core dovecot-imapd dovecot-pop3d dovecot-sieve dovecot-lmtpd opendkim opendkim-tools

# Eliminar archivos de configuración de Postfix
sudo rm -rf /etc/postfix
sudo rm -rf /var/spool/postfix

# Eliminar archivos de configuración de Dovecot
sudo rm -rf /etc/dovecot
sudo rm -rf /var/mail/vhosts

# Eliminar archivos de configuración de OpenDKIM
sudo rm -rf /etc/opendkim

# Actualizar la lista de paquetes y limpiar dependencias no necesarias
sudo apt update
sudo apt autoremove -y
sudo apt autoclean -y

# Confirmar desinstalación
echo "Postfix, Dovecot y OpenDKIM han sido desinstalados y su configuración eliminada."
