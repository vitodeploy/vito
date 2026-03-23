import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { FirewallRule } from '@/types/firewall';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import RuleForm from '@/pages/firewall/components/form';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

function Delete({ firewallRule }: { firewallRule: FirewallRule }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('firewall.destroy', { server: firewallRule.server_id, firewallRule: firewallRule }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete firewallRule [{firewallRule.name}]</DialogTitle>
          <DialogDescription className="sr-only">Delete firewallRule</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete rule <strong>{firewallRule.name}</strong>? This action cannot be undone.
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function Firewall() {
  const page = usePage<{
    server: Server;
    rules: DynamicTableData;
  }>();

  return (
    <ServerLayout>
      <Head title={`Firewall - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Firewall" description="Here you can manage server's firewall rules" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/firewall" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <RuleForm serverId={page.props.server.id}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </RuleForm>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.rules}
          realtimeEvent="firewall-rule"
          actions={(firewallRule) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <RuleForm serverId={(firewallRule as unknown as FirewallRule).server_id} firewallRule={firewallRule as unknown as FirewallRule}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </RuleForm>
                  <DropdownMenuSeparator />
                  <Delete firewallRule={firewallRule as unknown as FirewallRule} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
