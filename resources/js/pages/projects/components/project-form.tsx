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
import { FormEventHandler, ReactNode, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Project } from '@/types/project';
import FormSuccessful from '@/components/form-successful';

export default function ProjectForm({
  project,
  defaultOpen,
  onOpenChange,
  children,
}: {
  project?: Project;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(defaultOpen || false);
  useEffect(() => {
    if (defaultOpen) {
      setOpen(defaultOpen);
    }
  }, [setOpen, defaultOpen]);

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (onOpenChange) {
      onOpenChange(open);
    }
  };

  const form = useForm({
    name: project?.name || '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    if (project) {
      form.patch(route('projects.update', project.id));
      return;
    }

    form.post(route('projects.store'), {
      onSuccess() {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{project ? 'Edit Project' : 'Create Project'}</DialogTitle>
          <DialogDescription className="sr-only">{project ? 'Edit the project details.' : 'Create a new project.'}</DialogDescription>
        </DialogHeader>
        <Form id="project-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="project-form" type="button" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
