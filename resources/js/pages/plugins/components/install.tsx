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

export default function InstallPlugin({ plugin }: { plugin: Plugin }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    id: plugin.id,
  });

  const submit = () => {
    form.patch(route('plugins.install'), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Install</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Install plugin</DialogTitle>
          <DialogDescription className="sr-only">Install plugin {plugin.folder}</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to install the plugin located at <strong>{plugin.folder}</strong>?
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="default" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Install
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
