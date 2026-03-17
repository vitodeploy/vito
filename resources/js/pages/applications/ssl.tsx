import ServerLayout from '@/layouts/server/layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Application } from '@/types/application';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircle, MoreHorizontalIcon, PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/ssls/components/columns';
import { SSL } from '@/types/ssl';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useRealtime } from '@/hooks/use-socket-events';
import ToggleForceSSL from '@/pages/applications/components/toggle-force-ssl';
import { FormEvent, ReactNode, useState } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';

function CreateApplicationSSL({ server, application, children }: { server: Server; application: Application; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    type: '',
    email: '',
    certificate: '',
    private: '',
    expires_at: '',
    aliases: false,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('application-ssls.store', { server: server.id, application: application.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create SSL</DialogTitle>
          <DialogDescription className="sr-only">Create new SSL</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-app-ssl-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select onValueChange={(value) => form.setData('type', value)} defaultValue={form.data.type}>
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="letsencrypt">Let's Encrypt</SelectItem>
                  <SelectItem value="custom">Custom</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            {form.data.type === 'letsencrypt' && (
              <>
                <Alert>
                  <AlertDescription>
                    <p>
                      Let's Encrypt has rate limits. Read more about them{' '}
                      <a href="https://letsencrypt.org/docs/rate-limits/" target="_blank" className="underline">
                        here
                      </a>
                      .
                    </p>
                  </AlertDescription>
                </Alert>
                <FormField>
                  <Label htmlFor="email">Email</Label>
                  <Input type="text" id="email" name="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                  <InputError message={form.errors.email} />
                </FormField>
              </>
            )}
            {form.data.type === 'custom' && (
              <>
                <FormField>
                  <Label htmlFor="certificate">Certificate</Label>
                  <Textarea
                    id="certificate"
                    name="certificate"
                    value={form.data.certificate}
                    onChange={(e) => form.setData('certificate', e.target.value)}
                    className="max-h-60"
                  />
                  <InputError message={form.errors.certificate} />
                </FormField>
                <FormField>
                  <Label htmlFor="private">Private key</Label>
                  <Textarea
                    id="private"
                    name="private"
                    value={form.data.private}
                    onChange={(e) => form.setData('private', e.target.value)}
                    className="max-h-60"
                  />
                  <InputError message={form.errors.private} />
                </FormField>
                <FormField>
                  <Label htmlFor="expires-at">Expires at</Label>
                  <Input
                    id="date"
                    value={form.data.expires_at}
                    placeholder="YYYY-MM-DD"
                    className="bg-background pr-10"
                    onChange={(e) => form.setData('expires_at', e.target.value)}
                  />
                  <InputError message={form.errors.expires_at} />
                </FormField>
              </>
            )}
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="aliases" name="aliases" checked={form.data.aliases} onClick={() => form.setData('aliases', !form.data.aliases)} />
                <Label htmlFor="aliases">Set SSL for application's aliases as well</Label>
              </div>
              <InputError message={form.errors.aliases} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button type="button" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function ApplicationSSL() {
  const page = usePage<{
    server: Server;
    application: Application;
    ssls: PaginatedData<SSL>;
  }>();

  const [ssls] = useRealtime<SSL>(page.props.ssls, 'ssl');

  return (
    <ServerLayout>
      <Head title={`SSL - ${page.props.application.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSL" description="Manage SSL certificates for this application" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/ssl" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateApplicationSSL server={page.props.server} application={page.props.application}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">New SSL</span>
              </Button>
            </CreateApplicationSSL>
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <ToggleForceSSL application={page.props.application} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={ssls} />
      </Container>
    </ServerLayout>
  );
}
