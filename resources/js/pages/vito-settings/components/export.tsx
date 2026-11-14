import { Button } from '@/components/ui/button';
import { DownloadIcon } from 'lucide-react';

export default function ExportVito() {
  const submit = () => {
    window.open('/admin/vito/export', '_blank');
  };

  return (
    <Button onClick={submit}>
      <DownloadIcon />
      Export
    </Button>
  );
}
