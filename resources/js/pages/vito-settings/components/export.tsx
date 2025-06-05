import { Button } from '@/components/ui/button';
import { DownloadIcon } from 'lucide-react';

export default function ExportVito() {
  const submit = () => {
    window.open(route('vito-settings.export'), '_blank');
  };

  return (
    <Button onClick={submit}>
      <DownloadIcon />
      Export
    </Button>
  );
}
