import { Head, useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { CloudUploadIcon, LoaderCircleIcon } from 'lucide-react';
import { Backup } from '@/types/backup';
import { DataTable } from '@/components/data-table';
import { PaginatedData } from '@/types';
import { BackupFile } from '@/types/backup-file';
import { columns } from '@/pages/backups/components/file-columns';
import CopyableBadge from '@/components/copyable-badge';

type Page = {
  server: Server;
  backup: Backup;
  files: PaginatedData<BackupFile>;
};

export default function Files() {
  const page = usePage<Page>();

  const runBackupForm = useForm();
  const runBackup = () => {
    runBackupForm.post(route('backups.run', { server: page.props.server.id, backup: page.props.backup.id }));
  };

  return (
    <ServerLayout>
      <Head title={`Backup files - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="space-y-0.5">
            <h2 className="flex items-center gap-2 text-xl font-semibold tracking-tight">
              Backup files of
              {page.props.backup.type === 'database' && <CopyableBadge text={page.props.backup.database?.name} />}
              {page.props.backup.type === 'file' && <CopyableBadge text={page.props.backup.path} tooltip />}
            </h2>
            <p className="text-muted-foreground text-sm">Here you can manage the backup files</p>
          </div>
          <div className="flex items-center gap-2">
            <Button onClick={runBackup}>
              {runBackupForm.processing ? <LoaderCircleIcon className="animate-spin" /> : <CloudUploadIcon />}
              <span className="hidden lg:block">Run backup</span>
            </Button>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.files} />
      </Container>
    </ServerLayout>
  );
}
