import { Head, useForm, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import ServerLayout from '@/layouts/server/layout';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, XIcon } from 'lucide-react';
import SiteBanners from '@/components/site-banners';
import React, { FormEvent } from 'react';
import { LoadBalancerServer } from '@/types/load-balancer-server';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import FormSuccessful from '@/components/form-successful';
import ServerSelect from '@/pages/servers/components/server-select';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';

export default function LoadBalancer() {
  const page = usePage<{
    server: Server;
    site: Site;
    loadBalancerServers: LoadBalancerServer[];
  }>();

  const form = useForm<{
    method: 'round-robin' | 'least-connections' | 'ip-hash';
    servers: {
      load_balancer_id: number;
      ip: string;
      port: number;
      weight: string;
      backup: boolean;
    }[];
  }>({
    method: 'round-robin',
    servers: page.props.loadBalancerServers,
  });

  const addServer = () => {
    const newServer: LoadBalancerServer = {
      load_balancer_id: 0,
      ip: '',
      port: 80,
      weight: '100',
      backup: false,
      created_at: '',
      updated_at: '',
    };

    form.setData('servers', [...form.data.servers, newServer]);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('application.update-load-balancer', { server: page.props.server.id, site: page.props.site.id }), {
      preserveScroll: true,
    });
  };

  const getFieldError = (field: string): string | undefined => {
    return form.errors[field as keyof typeof form.errors];
  };

  return (
    <ServerLayout>
      <Head title={`${page.props.site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Load balancer" description="Here you can manage the load balancer configs" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/load-balancer" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
          </div>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <Card>
          <CardHeader>
            <CardTitle>Configs</CardTitle>
            <CardDescription>Modify load balancer configs</CardDescription>
          </CardHeader>
          <CardContent className="p-4">
            <Form>
              <FormFields>
                <FormField>
                  <Label htmlFor="method">Method</Label>
                  <Select
                    value={form.data.method}
                    onValueChange={(value) => form.setData('method', value as 'round-robin' | 'least-connections' | 'ip-hash')}
                  >
                    <SelectTrigger id="method">
                      <SelectValue placeholder="Select a method" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="round-robin">round-robin</SelectItem>
                        <SelectItem value="least-connections">least-connections</SelectItem>
                        <SelectItem value="ip-hash">ip-hash</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.method} />
                </FormField>

                {form.data.servers.map((item, index) => (
                  <div key={`server-${index}`} className="relative rounded-md border border-dashed p-4">
                    <XIcon
                      className="text-muted-foreground hover:text-foreground absolute top-2 right-2 cursor-pointer"
                      onClick={() => {
                        const updatedServers = [...form.data.servers];
                        updatedServers.splice(index, 1);
                        form.setData('servers', updatedServers);
                      }}
                    />
                    <div className="grid grid-cols-1 items-start gap-6 md:grid-cols-2 lg:grid-cols-4">
                      <FormField>
                        <Label htmlFor={`server-${index}`}>Server</Label>
                        <ServerSelect
                          id={`server-${index}`}
                          value={item.ip}
                          valueBy="local_ip"
                          onValueChange={(server) => {
                            const updatedServers = [...form.data.servers];
                            updatedServers[index] = {
                              ...updatedServers[index],
                              ip: server ? server.local_ip || '' : '',
                            };
                            form.setData('servers', updatedServers);
                          }}
                        />
                        <InputError message={getFieldError(`servers.${index}.ip`)} />
                      </FormField>

                      <FormField>
                        <Label htmlFor={`port-${index}`}>Port</Label>
                        <Input
                          id={`port-${index}`}
                          type="text"
                          value={item.port || ''}
                          onChange={(e) => {
                            const updatedServers = [...form.data.servers];
                            updatedServers[index] = {
                              ...updatedServers[index],
                              port: parseInt(e.target.value, 10),
                            };
                            form.setData('servers', updatedServers);
                          }}
                        />
                        <InputError message={getFieldError(`servers.${index}.port`)} />
                      </FormField>

                      <FormField>
                        <Label htmlFor={`weight-${index}`}>Weight</Label>
                        <Input
                          id={`weight-${index}`}
                          type="text"
                          value={item.weight || '100'}
                          onChange={(e) => {
                            const updatedServers = [...form.data.servers];
                            updatedServers[index] = {
                              ...updatedServers[index],
                              weight: e.target.value,
                            };
                            form.setData('servers', updatedServers);
                          }}
                        />
                        <InputError message={getFieldError(`servers.${index}.weight`)} />
                      </FormField>

                      <FormField>
                        <Label htmlFor={`backup-${index}`}>Backup</Label>
                        <Switch
                          id={`backup-${index}`}
                          checked={item.backup || false}
                          onCheckedChange={(checked) => {
                            const updatedServers = [...form.data.servers];
                            updatedServers[index] = {
                              ...updatedServers[index],
                              backup: checked,
                            };
                            form.setData('servers', updatedServers);
                          }}
                          className="mt-2"
                        />
                        <InputError message={getFieldError(`servers.${index}.backup`)} />
                      </FormField>
                    </div>
                  </div>
                ))}

                <FormField
                  onClick={addServer}
                  className="text-muted-foreground hover:text-foreground flex h-[92px] items-center justify-center rounded-md border border-dashed hover:cursor-pointer"
                >
                  Add server to the load balancer
                </FormField>
              </FormFields>
            </Form>
          </CardContent>
          <CardFooter>
            <Button disabled={form.processing} onClick={submit}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              <FormSuccessful successful={form.recentlySuccessful} />
              Save and Deploy
            </Button>
          </CardFooter>
        </Card>
      </Container>
    </ServerLayout>
  );
}
