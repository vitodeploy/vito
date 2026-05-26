import { Site } from '@/types/site';
import { ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

export default function StatsToggle({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();
  const enabled = site.stats_enabled;

  const submit = () => {
    const routeName = enabled ? 'site-settings.disable-stats' : 'site-settings.enable-stats';
    form.post(route(routeName, { server: site.server_id, site: site.id }), {
      preserveScroll: true,
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{enabled ? 'Disable statistics' : 'Enable statistics'}</DialogTitle>
          <DialogDescription className="sr-only">{enabled ? 'Disable' : 'Enable'} site statistics</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4 text-sm">
          {enabled ? (
            <p>
              This stops processing logs for <span className="font-medium">{site.domain}</span> and{' '}
              <span className="font-medium">permanently erases all historical statistics</span> for this site. This cannot be undone.
            </p>
          ) : (
            <p>
              Statistics collection will resume for <span className="font-medium">{site.domain}</span> from now on. Previous history is not recovered.
            </p>
          )}
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={enabled ? 'destructive' : 'default'} disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {enabled ? 'Disable' : 'Enable'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
