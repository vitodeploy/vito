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
import { Application } from '@/types/application';

export default function ToggleForceSSL({ application }: { application: Application }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    const url = application.force_ssl
      ? route('application-ssls.disable-force-ssl', { server: application.server_id, application: application.id })
      : route('application-ssls.enable-force-ssl', { server: application.server_id, application: application.id });
    form.post(url, {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>{application.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{application.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DialogTitle>
          <DialogDescription className="sr-only">{application.force_ssl ? 'Disable' : 'Enable'} Force-SSL</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to {application.force_ssl ? 'disable' : 'enable'} force-ssl?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={application.force_ssl ? 'destructive' : 'default'} disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {application.force_ssl ? 'Disable' : 'Enable'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
