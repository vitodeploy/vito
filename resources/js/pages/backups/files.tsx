import { Head, useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
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
import { useRealtime } from '@/hooks/use-socket-events';

type Page = {
  server: Server;
  backup: Backup;
  files: PaginatedData<BackupFile>;
};

export default function Files() {
  const page = usePage<Page>();
  const [files] = useRealtime<BackupFile>(page.props.files, 'backup-file', { backup_id: page.props.backup.id });

  const visibleColumns = useMemo(
    () =>
      page.props.backup.type === 'file'
        ? columns.filter((column) => !('accessorKey' in column && column.accessorKey === 'database_engine'))
        : columns,
    [page.props.backup.type],
  );

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

        <DataTable columns={visibleColumns} paginatedData={files} />
      </Container>
    </ServerLayout>
  );
}
