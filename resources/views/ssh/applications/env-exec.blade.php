{!! '#!/bin/bash -p' !!}
set -e
if [ "$#" -lt 2 ]; then
    printf '%s\n' 'Usage: vito-env-exec ENV_FILE COMMAND [ARG...]' >&2
    exit 1
fi
env_file=$1
shift
if [ -e "$env_file" ]; then
    umask 077
    records=$(/usr/bin/mktemp)
    trap '/bin/rm -f -- "$records"' EXIT
    LC_ALL=C /usr/bin/awk '
        function invalid() {
            printf "Invalid env file at line %d.\n", NR > "/dev/stderr"
            exit 1
        }
        /^[[:space:]]*$/ { next }
        {
            if (index($0, sprintf("%c", 0)) || $0 !~ /^[A-Z_][A-Z0-9_]*=/) invalid()
            separator = index($0, "=")
            key = substr($0, 1, separator - 1)
            if (key ~ /{!! \App\Models\EnvironmentVariable::RESERVED_KEY_PATTERN !!}/) invalid()
            value = substr($0, separator + 1)
            quote = substr(value, 1, 1)
            if (length(value) < 2 || substr(value, length(value), 1) != quote) invalid()
            value = substr(value, 2, length(value) - 2)
            decoded = ""
            if (quote == sprintf("%c", 39)) {
                if (index(value, quote) || index(value, "=\"") || index(value, "\r")) invalid()
                decoded = value
            } else if (quote == "\"") {
                for (i = 1; i <= length(value); i++) {
                    character = substr(value, i, 1)
                    if (character == "\\") {
                        character = substr(value, ++i, 1)
                        if (character == "n") character = "\n"
                        else if (character != "\\" && character != "\"" && character != "$") invalid()
                    } else if (character == "\"" || character == "$" || character == "\r") invalid()
                    decoded = decoded character
                }
            } else invalid()
            printf "%s=%s%c", key, decoded, 0
        }
    ' "$env_file" > "$records" || exit 1
    while IFS= read -r -d '' kv; do
        if ! export "$kv" 2>/dev/null; then
            printf '%s\n' 'Unable to export an environment variable.' >&2
            exit 1
        fi
    done < "$records"
    /bin/rm -f -- "$records"
    trap - EXIT
fi
exec "$@"
