import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage, useForm } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PlusIcon, PlayIcon, TrashIcon, FileIcon } from 'lucide-react';
import React, { useState } from 'react';
import { VitoBackup } from '@/types';
import { StorageProvider } from '@/types/storage-provider';
import CreateVitoBackupDialog from './components/create-dialog';
import EditVitoBackupDialog from './components/edit-dialog';
import DeleteVitoBackupDialog from './components/delete-dialog';
import BackupFilesDialog from './components/files-dialog';

function RunBackupButton({ backup }: { backup: VitoBackup }) {
  const form = useForm();

  const runBackup = () => {
    form.post(route('vito-backups.run', { vitoBackup: backup.id }));
  };

  return (
    <Button size="sm" variant="outline" onClick={runBackup} disabled={form.processing}>
      <PlayIcon />
    </Button>
  );
}

type Page = {
  vitoBackups: VitoBackup[];
  storageProviders: StorageProvider[];
};

export default function VitoBackups() {
  const page = usePage<Page>();
  const [createOpen, setCreateOpen] = useState(false);
  const [editBackup, setEditBackup] = useState<VitoBackup | null>(null);
  const [deleteBackup, setDeleteBackup] = useState<VitoBackup | null>(null);
  const [filesBackup, setFilesBackup] = useState<VitoBackup | null>(null);

  const getStatusColor = (status: string) => {
    const colors = {
      running: 'bg-blue-100 text-blue-800',
      success: 'bg-green-100 text-green-800',
      pending: 'bg-gray-100 text-gray-800',
      failed: 'bg-red-100 text-red-800',
      deleting: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
  };

  return (
    <SettingsLayout>
      <Head title="Vito Backups" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Vito Backups" description="Manage automated backups of Vito data" />
          <Button onClick={() => setCreateOpen(true)}>
            <PlusIcon />
            Create Backup
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Backup Configurations</CardTitle>
            <CardDescription>Configure automated backups that will be stored in your selected storage providers.</CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Storage Provider</TableHead>
                  <TableHead>Keep Backups</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Files</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {page.props.vitoBackups.map((backup) => (
                  <TableRow key={backup.id}>
                    <TableCell className="font-medium">{backup.name}</TableCell>
                    <TableCell>{backup.storage?.profile}</TableCell>
                    <TableCell>{backup.keep_backups}</TableCell>
                    <TableCell>
                      <Badge className={getStatusColor(backup.status)}>{backup.status}</Badge>
                    </TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" onClick={() => setFilesBackup(backup)} className="flex items-center gap-2">
                        <FileIcon className="h-4 w-4" />
                        {backup.files?.length || 0} file{(backup.files?.length || 0) !== 1 ? 's' : ''}
                      </Button>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <RunBackupButton backup={backup} />
                        <Button size="sm" variant="outline" onClick={() => setEditBackup(backup)}>
                          Edit
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => setDeleteBackup(backup)}>
                          <TrashIcon />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
                {page.props.vitoBackups.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-muted-foreground text-center">
                      No backups configured yet.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <CreateVitoBackupDialog open={createOpen} onOpenChange={setCreateOpen} storageProviders={page.props.storageProviders} />

        {editBackup && <EditVitoBackupDialog open={!!editBackup} onOpenChange={() => setEditBackup(null)} backup={editBackup} />}

        {deleteBackup && <DeleteVitoBackupDialog open={!!deleteBackup} onOpenChange={() => setDeleteBackup(null)} backup={deleteBackup} />}

        {filesBackup && <BackupFilesDialog open={!!filesBackup} onOpenChange={() => setFilesBackup(null)} backup={filesBackup} />}
      </Container>
    </SettingsLayout>
  );
}
