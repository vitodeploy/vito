import { ReactNode, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
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
import { Site } from '@/types/site';
import { Deployment } from '@/types/deployment';
import InputError from '@/components/ui/input-error';
import { Server } from '@/types/server';

export default function Rollback({ deployment, children }: { deployment: Deployment; children: ReactNode }) {
  const page = usePage<{
    server: Server;
    site: Site;
  }>();
  const [open, setOpen] = useState(false);
  const form = useForm({
    deployment: deployment.id,
  });

  const submit = () => {
    form.post(route('application.rollback', { server: page.props.server.id, site: page.props.site.id, deployment: deployment.id }), {
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
          <DialogTitle>Rollback</DialogTitle>
          <DialogDescription className="sr-only">Rollback the deployment</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to rollback your site to version [{deployment.release}]?</p>
          <InputError message={form.errors.deployment} />
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Rollback
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
