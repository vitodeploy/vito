import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { FirewallRule } from '@/types/firewall';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ firewallRule }: { firewallRule: FirewallRule }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete rule [${firewallRule.name}]`,
          description: `Are you sure you want to delete rule ${firewallRule.name}? This action cannot be undone.`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: `/servers/${firewallRule.server_id}/firewall/${firewallRule.id}`,
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
