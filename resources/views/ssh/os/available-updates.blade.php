sudo DEBIAN_FRONTEND=noninteractive apt-get update -o Acquire::AllowReleaseInfoChange::Label=true > /dev/null 2>&1

UPGRADABLE=$(sudo DEBIAN_FRONTEND=noninteractive apt list --upgradable 2>/dev/null | tail -n +2)

TOTAL=$(printf '%s\n' "$UPGRADABLE" | grep -c '/' || true)
KERNEL_UPDATES=$(printf '%s\n' "$UPGRADABLE" | grep -cE '^linux-(image|headers|modules|modules-extra|generic|virtual|lowlatency|hwe|tools|cloud-tools|aws|azure|gcp|oracle|kvm)[^ ]*/' || true)
REGULAR=$((TOTAL - KERNEL_UPDATES))

echo "Available updates:$REGULAR"
echo "Kernel updates:$KERNEL_UPDATES"
