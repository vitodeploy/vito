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

export default function UpdatePlugin({ plugin }: { plugin: Plugin }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    id: plugin.id,
  });

  const submit = () => {
    form.patch(route('plugins.update'), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Update</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update plugin</DialogTitle>
          <DialogDescription className="sr-only">Update plugin {plugin.name}</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to update the plugin <strong>{plugin.name}</strong> to the latest released version?
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="default" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Update
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
