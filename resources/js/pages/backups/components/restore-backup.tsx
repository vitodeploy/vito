import { Backup } from '@/types/backup';
import { BackupFile } from '@/types/backup-file';
import { Server } from '@/types/server';
import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import DatabaseSelect from '@/pages/databases/components/database-select';
import ServerSelect from '@/pages/servers/components/server-select';

export default function RestoreBackup({
  open,
  onOpenChange,
  backup,
  file,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  backup: Backup;
  file: BackupFile;
}) {
  const page = usePage<{ server?: Server }>();
  const sourceServerName = page.props.server?.name ?? 'Original server';
  const [targetServerId, setTargetServerId] = useState(backup.server_id);
  const [targetServerName, setTargetServerName] = useState<string | undefined>(sourceServerName);

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
        onSuccess: () => onOpenChange(false),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Restore backup</DialogTitle>
          <DialogDescription className="sr-only">Restore backup</DialogDescription>
        </DialogHeader>
        <Form id="restore-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            {backup.type === 'database' && (
              <>
                <div className="text-muted-foreground text-sm">
                  {file.database_engine
                    ? `Source: ${file.database_engine} ${file.database_version ?? ''}`.trim()
                    : 'Source database engine/version unknown - this file can only be restored to its original server.'}
                </div>
                <FormField>
                  <Label htmlFor="target-server">To server</Label>
                  <ServerSelect
                    id="target-server"
                    value={String(targetServerId)}
                    placeholder={targetServerName ?? 'Select server...'}
                    onValueChange={(server) => {
                      if (server) {
                        setTargetServerId(server.id);
                        setTargetServerName(server.name);
                      } else {
                        setTargetServerId(backup.server_id);
                        setTargetServerName(sourceServerName);
                      }
                      form.setData('database', '');
                    }}
                  />
                </FormField>
                <FormField>
                  <Label htmlFor="database">To database</Label>
                  <DatabaseSelect
                    id="database"
                    name="database"
                    serverId={targetServerId}
                    value={form.data.database}
                    onValueChange={(value) => form.setData('database', value)}
                  />
                  <InputError message={form.errors.database} />
                </FormField>
              </>
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
