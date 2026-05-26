import { Service } from '@/types/service';
import { useState } from 'react';
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
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import InputError from '@/components/ui/input-error';

export function ResyncStats({ service }: { service: Service }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('log-analysis.resync', { server: service.server_id }), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Re-sync stats scripts</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Re-sync stats scripts</DialogTitle>
          <DialogDescription className="sr-only">Re-sync GoAccess scripts and cron on the server</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            This re-writes the GoAccess processing scripts and per-site configs on the server and ensures the cron is in place. Run this after a Vito
            update.
          </p>
          {Object.entries(form.errors).map(([key, value]) => (
            <InputError key={key} message={value as string | undefined} />
          ))}
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Re-sync
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
