import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { SshKey } from '@/types/ssh-key';
import { columns } from '@/pages/ssh-keys/components/columns';
import AddSshKey from '@/pages/ssh-keys/components/add-ssh-key';
import { PaginatedData } from '@/types';
import { BookOpenIcon, PlusIcon } from 'lucide-react';

type Page = {
  sshKeys: PaginatedData<SshKey>;
};

export default function SshKeys() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="SSH Keys" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="SSH Keys" description="Here you can manage all of your ssh keys" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssh-keys" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <AddSshKey>
              <Button>
                <PlusIcon />
                Add
              </Button>
            </AddSshKey>
          </div>
        </div>

        <DataTable columns={columns} paginatedData={page.props.sshKeys} />
      </Container>
    </SettingsLayout>
  );
}
