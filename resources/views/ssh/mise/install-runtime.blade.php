mise install {{ $runtime . '@' . $version }}

mise exec {{ $runtime . '@' . $version }} -- {{ $runtime }} --version
echo "{{ $runtime . '@' . $version }} installed successfully"
