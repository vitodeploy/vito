import { Server } from '@/types/server';
import { FormEvent, ReactNode, useState } from 'react';
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
import { useForm, usePage } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import type { SharedData } from '@/types';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function TransferServer({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();

  const form = useForm({
    project_id: server.project_id.toString(),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('servers.transfer', { server: server.id }), {
      preserveScroll: true,
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
          <DialogTitle>Transfer {server.name}</DialogTitle>
          <DialogDescription className="sr-only">Transfer server to another project</DialogDescription>
        </DialogHeader>

        <Form id="transfer-server-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="project_id">Project</Label>
              <Select value={form.data.project_id} onValueChange={(value) => form.setData('project_id', value)}>
                <SelectTrigger id="project_id">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {page.props.auth.projects.map((project) => (
                    <SelectItem key={project.id} value={project.id.toString()}>
                      {project.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.project_id} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="transfer-server-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Transfer server
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
