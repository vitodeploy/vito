import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { DNSProvider } from '@/types/dns-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Edit({ dnsProvider }: { dnsProvider: DNSProvider }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.dnsProviderEdit.open({ dnsProvider })}>Edit</DropdownMenuItem>;
}
