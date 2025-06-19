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

export default function Uninstall({ plugin }: { plugin: Plugin }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    name: plugin.name,
  });

  const submit = () => {
    form.delete(route('plugins.uninstall'), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">Uninstall</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Uninstall plugin</DialogTitle>
          <DialogDescription className="sr-only">Uninstall plugin {plugin.name}</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to uninstall the plugin <strong>{plugin.name}</strong>?
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Uninstall
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
