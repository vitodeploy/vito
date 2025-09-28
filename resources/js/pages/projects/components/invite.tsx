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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Project } from '@/types/project';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';

export default function Invite({ project, onInviteSent, children }: { project: Project; onInviteSent?: () => void; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    email: '',
    role: 'user',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/settings/projects/${project.id}/users`, {
      onSuccess: () => {
        setOpen(false);
        if (onInviteSent) {
          onInviteSent();
        }
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Invite users to project</DialogTitle>
          <DialogDescription className="sr-only">Invite a new user to project</DialogDescription>
        </DialogHeader>
        <Form id="invite-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="email">Email</Label>
              <Input id="email" name="email" type="email" onChange={(e) => form.setData('email', e.target.value)} />
              <InputError message={form.errors.email} />
            </FormField>
            <FormField>
              <Label htmlFor="role">Role</Label>
              <Select defaultValue={form.data.role} onValueChange={(value) => form.setData('role', value)}>
                <SelectTrigger id="role" name="role" className="w-full">
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="user">User</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.role} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="invite-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Invite
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
