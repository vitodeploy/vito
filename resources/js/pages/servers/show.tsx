import { Head, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import { type Server } from '@/types/server';
import InstallingServer from '@/pages/servers/installing';
import type { ServerLog } from '@/types/server-log';
import ServerOverview from '@/pages/servers/overview';
import ServerLayout from '@/layouts/server/layout';

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
    <ServerLayout server={page.props.server}>
      <Head title={`Overview - ${page.props.server.name}`} />

      {['installing', 'installation_failed'].includes(page.props.server.status) ? <InstallingServer /> : <ServerOverview />}
    </ServerLayout>
  );
}
