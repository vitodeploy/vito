import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function Extensions({ service }: { service: Service }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.phpExtensions.open({ service })}>Extensions</DropdownMenuItem>;
}
