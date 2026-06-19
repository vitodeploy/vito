import { Head, router, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { Backup } from '@/types/backup';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/backups/components/columns';
import { PaginatedData } from '@/types';
import { useRealtime, useSocketListener } from '@/hooks/use-socket-events';
import { useDialog } from '@/hooks/use-dialog';
import { useCallback, useEffect, useRef } from 'react';

type Page = {
  server: Server;
  backups: PaginatedData<Backup>;
};

export default function Backups() {
  const page = usePage<Page>();
  const dialog = useDialog();
  const [backups] = useRealtime<Backup>(page.props.backups, 'backup', { server_id: page.props.server.id });

  const reloadTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const scheduleReload = useCallback(() => {
    if (reloadTimer.current) {
      clearTimeout(reloadTimer.current);
    }
    reloadTimer.current = setTimeout(() => {
      router.reload({ only: ['backups'] });
    }, 750);
  }, []);

  useSocketListener(
    useCallback(
      (event) => {
        if (event.type !== 'backup-file.created' && event.type !== 'backup-file.updated') {
          return;
        }
        const backupId = event.data?.backup_id;
        if (typeof backupId === 'number' && backups.data.some((backup) => backup.id === backupId)) {
          scheduleReload();
        }
      },
      [backups.data, scheduleReload],
    ),
  );

  useEffect(() => {
    return () => {
      if (reloadTimer.current) {
        clearTimeout(reloadTimer.current);
      }
    };
  }, []);

  return (
    <ServerLayout>
      <Head title={`Backups - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Backups" description="Here you can manage database and file backups" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database#backup" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button onClick={() => dialog.backupCreate.open({ server: page.props.server })}>
              <PlusIcon />
              <span className="hidden lg:block">Create</span>
            </Button>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={backups} />
      </Container>
    </ServerLayout>
  );
}
