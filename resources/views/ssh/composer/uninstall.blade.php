@if ($configDir !== null)
rm -f -- {!! escapeshellarg($configDir.'/'.$installDir.'/composer') !!}
rmdir -- {!! escapeshellarg($configDir.'/'.$installDir) !!} 2>/dev/null || true
@else
rm -f "$HOME/{{ $installDir }}/composer"

ACTIVATION_LINE='export PATH="$HOME/{{ $installDir }}:$PATH"'
for FILE in "$HOME/.bashrc" "$HOME/.profile"; do
    [ -f "$FILE" ] || continue
    { grep -vFx "$ACTIVATION_LINE" "$FILE" || true; } > "$FILE.vito.tmp"
    mv "$FILE.vito.tmp" "$FILE"
done

rmdir "$HOME/{{ $installDir }}" 2>/dev/null || true
@endif
