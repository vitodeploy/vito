export PATH=$HOME/.local/share/mise/shims:$PATH

mise unuse -g {{ $runtime }} || true
mise uninstall {{ $runtime }} --all

echo "{{ $runtime }} uninstalled successfully"
