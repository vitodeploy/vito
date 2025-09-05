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
import { useState } from 'react';
import { LoaderCircleIcon } from 'lucide-react';
import { Plugin } from '@/types/plugin';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';

export default function DeleteLogs({ plugin }: { plugin: Plugin }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    id: plugin.id,
  });

  const submit = () => {
    form.delete(route('plugins.logs'), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Delete Logs</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Error Logs</DialogTitle>
          <DialogDescription className="sr-only">Delete error logs for {plugin.name ?? plugin.folder}</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete the error logs for the plugin <strong>{plugin.name ?? plugin.folder}</strong>?
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Delete Logs
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
