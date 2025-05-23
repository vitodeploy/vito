import { Backup } from '@/types/backup';
import { BackupFile } from '@/types/backup-file';
import { useForm } from '@inertiajs/react';
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
import { LoaderCircleIcon } from 'lucide-react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import DatabaseSelect from '@/pages/database-users/components/database-select';
import InputError from '@/components/ui/input-error';

export default function RestoreBackup({
  backup,
  file,
  onBackupRestored,
  children,
}: {
  backup: Backup;
  file: BackupFile;
  onBackupRestored?: () => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    database: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(
      route('backup-files.restore', {
        server: backup.server_id,
        backup: backup.id,
        backupFile: file.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
          if (onBackupRestored) {
            onBackupRestored();
          }
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Restore backup</DialogTitle>
          <DialogDescription className="sr-only">Restore backup</DialogDescription>
        </DialogHeader>
        <Form id="restore-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="database">To database</Label>
              <DatabaseSelect
                id="database"
                name="database"
                serverId={backup.server_id}
                value={form.data.database}
                onValueChange={(value) => form.setData('database', value)}
              />
              <InputError message={form.errors.database} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline" disabled={form.processing}>
              Cancel
            </Button>
          </DialogClose>
          <Button form="restore-backup-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Restore
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
