import { ClipboardCheckIcon, ClipboardIcon, LoaderCircle, PlusIcon, TriangleAlert, WifiIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ServerProvider } from '@/types/server-provider';
import CreateServerProvider from '@/pages/server-providers/create-server-provider';

export default function CreateServer({ providers, public_key }: { providers: string[]; public_key: string }) {
  const page = usePage<{
    server_providers: ServerProvider[];
  }>();

  const form = useForm({
    provider: 'custom',
    server_provider: '',
    name: '',
    ip: '',
    port: 22,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('servers'));
  };

  const [copySuccess, setCopySuccess] = useState(false);
  const copyToClipboard = () => {
    navigator.clipboard.writeText(public_key).then(() => {
      setCopySuccess(true);
      setTimeout(() => {
        setCopySuccess(false);
      }, 2000);
    });
  };

  const selectProvider = (provider: string) => {
    form.setData('provider', provider);
    form.clearErrors();
    if (provider !== 'custom') {
      form.setData('server_provider', '');
    }
  };

  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button variant="outline">
          <PlusIcon />
          Create new server
        </Button>
      </SheetTrigger>
      <SheetContent className="w-full lg:max-w-4xl">
        <SheetHeader>
          <SheetTitle>Create new server</SheetTitle>
          <SheetDescription>Fill in the details to create a new server.</SheetDescription>
        </SheetHeader>
        <form id="create-server-form" className="flex w-full flex-col gap-6 p-4" onSubmit={submit}>
          <div className="grid gap-6">
            <div className="grid gap-2">
              <Label htmlFor="provider">Provider</Label>
              <Select value={form.data.provider} onValueChange={(value) => selectProvider(value)}>
                <SelectTrigger id="provider">
                  <SelectValue placeholder="Select a provider" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {providers.map((provider) => (
                      <SelectItem key={provider} value={provider}>
                        {provider}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError />
            </div>

            {form.data.provider && form.data.provider !== 'custom' && (
              <div className="grid gap-2">
                <Label htmlFor="server-provider">Server provider connection</Label>
                <div className="flex items-center gap-2">
                  <Select value={form.data.server_provider} onValueChange={(value) => form.setData('server_provider', value)}>
                    <SelectTrigger id="provider">
                      <SelectValue placeholder="Select a provider" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {page.props.server_providers
                          .filter((item: ServerProvider) => item.provider === form.data.provider)
                          .map((provider) => (
                            <SelectItem key={`server-provider-${provider.id}`} value={provider.id.toString()}>
                              {provider.name}
                            </SelectItem>
                          ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <CreateServerProvider
                    trigger="icon"
                    providers={providers.filter((item) => item !== 'custom')}
                    defaultProvider={form.data.provider}
                  />
                </div>
                <InputError />
              </div>
            )}

            {form.data.provider === 'custom' && (
              <>
                <Alert>
                  <TriangleAlert size={5} />
                  <AlertDescription>
                    Your server needs to have a new unused installation of supported operating systems and must have a root user. To get started, add
                    our public key to /root/.ssh/authorized_keys file by running the bellow command on your server as root.
                  </AlertDescription>
                </Alert>
                <div className="grid gap-2">
                  <Label htmlFor="public_key">Public Key</Label>
                  <Button
                    onClick={copyToClipboard}
                    variant="outline"
                    id="public_key"
                    type="button"
                    value={public_key}
                    className="justify-between truncate font-normal"
                  >
                    <span className="w-full max-w-2/3 overflow-x-hidden overflow-ellipsis">{public_key}</span>
                    {copySuccess ? <ClipboardCheckIcon size={5} className="text-success!" /> : <ClipboardIcon size={5} />}
                  </Button>
                </div>
              </>
            )}

            <div className="grid gap-2">
              <Label htmlFor="name">Server Name</Label>
              <Input id="name" type="text" autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </div>

            {form.data.provider === 'custom' && (
              <div className="grid grid-cols-2 gap-6">
                <div className="grid gap-2">
                  <Label htmlFor="ip">SSH IP</Label>
                  <Input id="ip" type="text" autoComplete="ip" value={form.data.ip} onChange={(e) => form.setData('ip', e.target.value)} />
                  <InputError message={form.errors.ip} />
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="port">SSH Port</Label>
                  <Input
                    id="port"
                    type="text"
                    autoComplete="port"
                    value={form.data.port}
                    onChange={(e) => form.setData('port', parseInt(e.target.value))}
                  />
                  <InputError message={form.errors.port} />
                </div>
              </div>
            )}
          </div>
        </form>
        <SheetFooter>
          <div className="flex items-center">
            <Button type="submit" form="create-server-form" tabIndex={4} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Create
            </Button>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
