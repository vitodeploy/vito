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

export default function RebootServer({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('servers.reboot', server.id), {
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
          <DialogTitle>Restart {server.name}?</DialogTitle>
          <DialogDescription className="sr-only">Restart server</DialogDescription>
        </DialogHeader>

        <div className="flex flex-col gap-2 p-4 text-sm">
          <p>Are you sure you want to restart this server?</p>
          <p className="text-muted-foreground">
            Sites and services hosted on this server will be unavailable while it restarts. Connections in flight will be dropped.
          </p>
        </div>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Restart
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
