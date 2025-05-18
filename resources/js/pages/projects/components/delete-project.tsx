import { FormEvent, ReactNode } from 'react';
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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Project } from '@/types/project';

export default function DeleteProject({ project, children }: { project: Project; children: ReactNode }) {
  const form = useForm({
    name: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(route('projects.destroy', project.id));
  };

  return (
    <Dialog>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {project.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete project and all its resources.</DialogDescription>
        </DialogHeader>

        <Form id="delete-project-form" onSubmit={submit} className="p-4">
          <p>Are you sure you want to delete this project? This action cannot be undone.</p>
          <FormFields>
            <FormField>
              <Label htmlFor="project-name">Name</Label>
              <Input id="project-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="delete-project-form" variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Delete Project
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
