import { Service } from '@/types/service';
import React, { useState } from 'react';
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

export function Action({ type, service }: { type: 'start' | 'stop' | 'restart' | 'enable' | 'disable'; service: Service }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route(`services.${type}`, { server: service.server_id, service: service }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()} className="capitalize">
          {type}
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            <span className="capitalize">{type}</span> service
          </DialogTitle>
          <DialogDescription className="sr-only">{type} service</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to {type} the service?</p>
          {Object.entries(form.errors).map(([key, value]) => (
            <InputError key={key} message={value} />
          ))}
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button
            variant={['disable', 'stop'].includes(type) ? 'destructive' : 'default'}
            disabled={form.processing}
            onClick={submit}
            className="capitalize"
          >
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {type}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
