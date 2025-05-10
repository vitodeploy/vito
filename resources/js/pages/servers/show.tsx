import { Head, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import AppLayout from '@/layouts/app-layout';
import { type Server } from '@/types/server';
import InstallingServer from '@/pages/servers/installing';
import type { ServerLog } from '@/types/server-log';

type Response = {
  servers: {
    data: Server[];
  };
  logs: {
    data: ServerLog[];
  };
  server: Server;
  public_key: string;
  configs: Configs;
};

export default function ShowServer() {
  const page = usePage<Response>();
  return (
    <AppLayout>
      <Head title={page.props.server.name} />

      {['installing', 'installation_failed'].includes(page.props.server.status) && <InstallingServer />}
    </AppLayout>
  );
}
