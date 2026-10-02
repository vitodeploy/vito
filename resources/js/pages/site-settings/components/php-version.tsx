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
import ServiceVersionSelect from '@/pages/services/components/service-version-select';

export default function ChangePHPVersion({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    version: string;
  }>({
    version: site.php_version || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(`/servers/${site.server_id}/sites/${site.id}/settings/php-version`, {
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
          <DialogTitle>Change PHP version</DialogTitle>
          <DialogDescription className="sr-only">Change site's php version.</DialogDescription>
        </DialogHeader>

        <Form id="change-php-version-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="versino">PHP version</Label>
              <ServiceVersionSelect
                serverId={site.server_id}
                service="php"
                value={form.data.version}
                onValueChange={(value) => form.setData('version', value)}
              />
              <InputError message={form.errors.version} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="change-php-version-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
