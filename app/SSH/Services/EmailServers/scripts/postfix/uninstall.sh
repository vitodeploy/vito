#!/bin/bash

# Detener los servicios de Postfix y Dovecot
sudo systemctl stop postfix
sudo systemctl stop dovecot

# Deshabilitar los servicios para que no se inicien al arrancar
sudo systemctl disable postfix
sudo systemctl disable dovecot

# Desinstalar Postfix y Dovecot
sudo apt purge -y postfix dovecot-core dovecot-imapd dovecot-pop3d dovecot-sieve dovecot-lmtpd

# Eliminar los archivos de configuración y datos de Postfix y Dovecot
sudo rm -rf /etc/postfix
sudo rm -rf /etc/dovecot
sudo rm -rf /var/mail
sudo rm -rf /var/spool/postfix

# Limpiar paquetes no utilizados y archivos residuales
sudo apt autoremove -y
sudo apt clean

echo "Desinstalación de Postfix y Dovecot completada."
