import { Server } from '@/types/server';
import { ReactNode, useState } from 'react';
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
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';

export default function UpdateServer({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('servers.update', server.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update {server.name}?</DialogTitle>
          <DialogDescription className="sr-only">Update server</DialogDescription>
        </DialogHeader>

        <div className="flex flex-col gap-2 p-4 text-sm">
          <p>
            Apply <b>{server.updates}</b> pending OS package {server.updates === 1 ? 'update' : 'updates'} to this server?
          </p>
          <p className="text-muted-foreground">
            The upgrade can take several minutes and may briefly restart affected services (web server, PHP-FPM, databases) as their packages are
            replaced. In-flight connections to sites hosted here may be dropped. If the kernel or a core library is upgraded, a server restart may be
            required afterwards.
          </p>
        </div>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Update
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
