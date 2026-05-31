import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Command } from '@/types/command';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';

export default function EditCommand({ open, onOpenChange, command }: { open: boolean; onOpenChange: (open: boolean) => void; command: Command }) {
  const form = useForm<{
    name: string;
    command: string;
  }>({
    name: command.name,
    command: command.command,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('commands.update', { server: command.server_id, site: command.site_id, command: command.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit command</DialogTitle>
          <DialogDescription className="sr-only">Create a new command</DialogDescription>
        </DialogHeader>
        <Form id="create-command-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="command">Command</Label>
              <Textarea id="command" name="command" value={form.data.command} onChange={(e) => form.setData('command', e.target.value)} />
              <p className="text-muted-foreground text-sm">
                You can use variables like {'${VARIABLE_NAME}'} in the command. The variables will be asked when executing the command
              </p>
              <InputError message={form.errors.command} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <div className="flex items-center gap-2">
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button form="create-command-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Save
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
