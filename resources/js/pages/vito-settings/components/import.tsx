import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, UploadIcon } from 'lucide-react';
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
import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';

export default function ImportVito() {
  const form = useForm({
    backup_file: null as File | null,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('vito-settings.import'));
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="outline">
          <UploadIcon />
          Import
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Import</DialogTitle>
          <DialogDescription className="sr-only">Import settings to Vito</DialogDescription>
        </DialogHeader>
        <Form id="import-vito-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="backup_file">Backup file</Label>
              <Input
                type="file"
                id="backup_file"
                name="backup_file"
                accept=".zip"
                onChange={(e) => form.setData('backup_file', e.target.files?.[0] || null)}
              />
              <InputError message={form.errors.backup_file} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="import-vito-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Import
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
