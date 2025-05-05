import { Head, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import AppLayout from '@/layouts/app-layout';
import { type Server } from '@/types/server';
import Container from '@/components/container';
import Heading from '@/components/heading';

type Response = {
  servers: {
    data: Server[];
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

      <Container>
        <div className="flex items-start justify-between">
          <Heading title={page.props.server.name} />
        </div>
      </Container>
    </AppLayout>
  );
}
