INSTALL_DIR="$HOME/{{ $installDir }}"
mkdir -p "$INSTALL_DIR"
cd "$HOME"

php{{ $phpVersion }} -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

if ! php{{ $phpVersion }} -r "exit(hash_file('sha384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig')) ? 0 : 1);"; then
    rm -f composer-setup.php
    echo 'VITO_SSH_ERROR' && exit 1
fi

php{{ $phpVersion }} composer-setup.php --{{ $majorVersion }} --install-dir="$INSTALL_DIR" --filename=composer

rm -f composer-setup.php

ACTIVATION_LINE='export PATH="$HOME/{{ $installDir }}:$PATH"'
for FILE in "$HOME/.bashrc" "$HOME/.profile"; do
    touch "$FILE"
    if ! grep -Fqx "$ACTIVATION_LINE" "$FILE"; then
        printf '\n%s\n' "$ACTIVATION_LINE" >> "$FILE"
    fi
done

"$INSTALL_DIR/composer" --version
