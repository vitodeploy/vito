import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { DNSProvider } from '@/types/dns-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ dnsProvider }: { dnsProvider: DNSProvider }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${dnsProvider.name}`,
          description: `Are you sure you want to delete ${dnsProvider.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('dns-providers.destroy', dnsProvider.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
