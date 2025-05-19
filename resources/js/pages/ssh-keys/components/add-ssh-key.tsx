import { LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type SshKeyForm = {
  name: string;
  public_key: string;
};

export default function AddSshKey({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const form = useForm<Required<SshKeyForm>>({
    name: '',
    public_key: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('ssh-keys.store'), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Add ssh key</DialogTitle>
          <DialogDescription className="sr-only">Add new ssh key</DialogDescription>
        </DialogHeader>
        <Form id="add-ssh-key-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" name="name" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="key">Public key</Label>
              <Textarea name="public_key" id="public_key" value={form.data.public_key} onChange={(e) => form.setData('public_key', e.target.value)} />
              <InputError message={form.errors.public_key} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button type="button" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Connect
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
