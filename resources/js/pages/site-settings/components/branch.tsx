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
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';
import SelectBranch from '@/pages/source-controls/components/select-branch';

export default function ChangeBranch({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    branch: string;
  }>({
    branch: site.branch || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-settings.update-branch', { server: site.server_id, site: site.id }), {
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
          <DialogTitle>Change branch</DialogTitle>
          <DialogDescription className="sr-only">Change site's source control branch.</DialogDescription>
        </DialogHeader>

        <Form id="change-branch-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="branch">Branch</Label>
              <SelectBranch
                sourceControlId={site.source_control_id.toString()}
                repository={site.repository}
                value={form.data.branch}
                onValueChange={(value) => form.setData('branch', value)}
              />
              <InputError message={form.errors.branch} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="change-branch-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
