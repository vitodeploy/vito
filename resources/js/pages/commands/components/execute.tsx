import React, { ReactNode, useState } from 'react';
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
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { Command } from '@/types/command';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';

export default function Execute({ command, children }: { command: Command; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<Record<string, string>>({});

  const submit = () => {
    form.post(`/servers/${command.server_id}/sites/${command.site_id}/commands/${command.id}/execute`, {
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
          <DialogTitle>Execute</DialogTitle>
          <DialogDescription className="sr-only">Execute command</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to run this command?</p>
          <Form id="execute-command-form" onSubmit={submit}>
            <FormFields>
              {command.variables.map((variable: string) => (
                <FormField key={`variable-${variable}`}>
                  <Label htmlFor={variable}>{variable}</Label>
                  <Input id={variable} name={variable} value={form.data[variable] || ''} onChange={(e) => form.setData(variable, e.target.value)} />
                  <InputError message={form.errors[variable as keyof typeof form.errors]} />
                </FormField>
              ))}
            </FormFields>
          </Form>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Execute
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
