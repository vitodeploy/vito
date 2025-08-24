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
import { Deployment } from '@/types/deployment';
import { Server } from '@/types/server';
import { Site } from '@/types/site';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { ReactNode, useState } from 'react';

export default function DeleteDeployment({ deployment, children }: { deployment: Deployment; children: ReactNode }) {
  const page = usePage<{
    server: Server;
    site: Site;
  }>();
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('application.deployments.destroy', { server: page.props.server, site: page.props.site.id, deployment: deployment.id }), {
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
          <DialogTitle>Delete release [{deployment.release || deployment.id}]</DialogTitle>
          <DialogDescription className="sr-only">Delete release</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete release <strong>{deployment.release || deployment.id}</strong>?
          </p>
          <p>This will delete the release files form the server. This action cannot be undone.</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
