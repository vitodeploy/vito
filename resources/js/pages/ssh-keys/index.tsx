import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import React from 'react';
import { SSHKey } from '@/types/ssh-key';
import { columns } from '@/pages/ssh-keys/components/columns';
import AddSshKey from '@/pages/ssh-keys/components/add-ssh-key';

type Page = {
  sshKeys: {
    data: SSHKey[];
  };
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
            <AddSshKey>
              <Button>Add</Button>
            </AddSshKey>
          </div>
        </div>

        <DataTable columns={columns} data={page.props.sshKeys.data} />
      </Container>
    </SettingsLayout>
  );
}
