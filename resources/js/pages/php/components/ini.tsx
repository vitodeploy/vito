import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function PHPIni({ service, type }: { service: Service; type: 'fpm' | 'cli' }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.phpIni.open({ service, type })}>Edit {type} ini</DropdownMenuItem>;
}
