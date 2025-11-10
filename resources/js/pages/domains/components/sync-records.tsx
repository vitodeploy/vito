import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, RefreshCwIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Domain } from '@/types/domain';

export default function SyncRecords({ domain }: { domain: Domain }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('dns-records.sync', domain.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <RefreshCwIcon />
          <span className="hidden lg:block">Sync</span>
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Sync DNS Records</DialogTitle>
          <DialogDescription className="sr-only">Sync DNS records from the DNS provider to Vito.</DialogDescription>
        </DialogHeader>
        <p className="p-4">This will delete the copy of the DNS records in vito and fetch them again from the provider</p>
        <DialogFooter>
          <DialogTrigger asChild>
            <Button variant="outline">Cancel</Button>
          </DialogTrigger>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Sync
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
