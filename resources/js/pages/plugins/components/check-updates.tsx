import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';

export default function CheckForUpdates() {
  const form = useForm();

  const submit = () => {
    form.get(route('plugins.updates'));
  };

  return (
    <Button variant="outline" onClick={submit} disabled={form.processing}>
      {form.processing ? <RefreshCw className="animate-spin" /> : <RefreshCw />}
      <span className="hidden lg:block">Check for Updates</span>
    </Button>
  );
}
