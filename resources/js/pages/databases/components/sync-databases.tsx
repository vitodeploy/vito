import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Server } from '@/types/server';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, RefreshCwIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function SyncDatabases({ server }: { server: Server }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.patch(route('databases.sync', server.id), {
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
          <DialogTitle>Sync Databases</DialogTitle>
          <DialogDescription className="sr-only">Sync databases from the server to Vito.</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to sync the databases from the server to Vito?</p>
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
