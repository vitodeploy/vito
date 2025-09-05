import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { RefreshCw } from 'lucide-react';

export default function CheckForUpdates() {
  const form = useForm();

  const submit = () => {
    form.get(route('plugins.updates'));
  };

  return (
    <Button variant="outline" onClick={submit} disabled={form.processing}>
      <RefreshCw />
      {form.processing && <LoaderCircleIcon className="animate-spin" />}
      <span className="hidden lg:block">Check for Updates</span>
    </Button>
  );
}
