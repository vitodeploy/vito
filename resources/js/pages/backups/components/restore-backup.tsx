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
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import DatabaseSelect from '@/pages/databases/components/database-select';

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
    path: '',
    owner: 'vito:vito',
    permissions: '755',
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
            {backup.type === 'database' && (
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
            )}
            {backup.type === 'file' && (
              <>
                <FormField>
                  <Label htmlFor="path">Restore to path</Label>
                  <Input
                    id="path"
                    name="path"
                    type="text"
                    placeholder="/home/username/restore-path"
                    value={form.data.path}
                    onChange={(e) => form.setData('path', e.target.value)}
                  />
                  <InputError message={form.errors.path} />
                </FormField>

                <FormField>
                  <Label htmlFor="owner">Owner *</Label>
                  <Input
                    id="owner"
                    name="owner"
                    type="text"
                    placeholder="vito:vito"
                    value={form.data.owner}
                    onChange={(e) => form.setData('owner', e.target.value)}
                  />
                  <div className="text-muted-foreground mt-1 text-sm">
                    Default: vito:vito. If using isolated users, change this field. Examples: "user1", "user1:group1", "root:root"
                  </div>
                  <InputError message={form.errors.owner} />
                </FormField>

                <FormField>
                  <Label htmlFor="permissions">Permissions *</Label>
                  <Input
                    id="permissions"
                    name="permissions"
                    type="text"
                    placeholder="755"
                    value={form.data.permissions}
                    onChange={(e) => form.setData('permissions', e.target.value)}
                  />
                  <div className="text-muted-foreground mt-1 text-sm">Format: 3-4 digits (e.g., 755, 644, 0755)</div>
                  <InputError message={form.errors.permissions} />
                </FormField>
              </>
            )}
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
