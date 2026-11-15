if [ -f Cargo.lock ]; then
  cargo build --release --locked
else
  cargo build --release
fi
