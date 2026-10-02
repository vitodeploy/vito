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
import { Site } from '@/types/site';

export default function AutoDeployment({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    const url = site.auto_deploy
      ? `/servers/${site.server_id}/sites/${site.id}/disable-auto-deployment`
      : `/servers/${site.server_id}/sites/${site.id}/enable-auto-deployment`;
    form.post(url, {
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
          <DialogTitle>{site.auto_deploy ? 'Disable' : 'Enable'} auto deployment</DialogTitle>
          <DialogDescription className="sr-only">{site.auto_deploy ? 'Disable' : 'Enable'} auto deployment</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to {site.auto_deploy ? 'disable' : 'enable'} auto deployment?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Yes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
