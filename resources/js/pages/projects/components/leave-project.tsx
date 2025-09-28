import { FormEvent, ReactNode, useState } from 'react';
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
import { Project } from '@/types/project';

export default function LeaveProject({ project, children }: { project: Project; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(`/settings/projects/${project.id}/leave`, {
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
          <DialogTitle>Leave {project.name}</DialogTitle>
          <DialogDescription className="sr-only">Leave project {project.name}</DialogDescription>
        </DialogHeader>

        <p className="p-4">Are you sure you want to leave this project?</p>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button onClick={submit} variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Leave
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
