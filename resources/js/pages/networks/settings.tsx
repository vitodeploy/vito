import { Head, useForm, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import React, { useState } from 'react';
import NetworkLayout from '@/layouts/network/layout';
import { useDialog } from '@/hooks/use-dialog';
import { formatDateString } from '@/lib/utils';
import { Network } from '@/types/network';

export default function NetworkSettings() {
  const page = usePage<{ network: Network }>();
  const network = page.props.network;
  const dialog = useDialog();
  const isProvider = network.type_value === 'provider';

  const [editMode, setEditMode] = useState<string | undefined>();

  const form = useForm<{ name: string }>({
    name: network.name,
  });

  const submit = () => {
    form.put(route('networks.update', { network: network.id }), {
      preserveScroll: true,
      onSuccess: () => setEditMode(undefined),
    });
  };

  const handleEnterKey = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      submit();
    }
  };

  return (
    <NetworkLayout>
      <Head title={`Settings - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Settings" description="Manage this network's settings" />
        </HeaderContainer>

        <Card className="overflow-hidden">
          <CardHeader className="flex-row items-center justify-between gap-2">
            <div className="space-y-2">
              <CardTitle>Network details</CardTitle>
              <CardDescription>Update network details</CardDescription>
            </div>
            <div className="flex items-center gap-2">
              {form.isDirty && (
                <Button onClick={submit}>
                  {form.processing && <LoaderCircleIcon className="animate-spin" />}
                  Save changes
                </Button>
              )}
              {(editMode || form.isDirty) && (
                <Button
                  variant="outline"
                  onClick={() => {
                    setEditMode(undefined);
                    form.reset();
                  }}
                >
                  Cancel
                </Button>
              )}
            </div>
          </CardHeader>
          <CardContent className="bg-background">
            <div className="flex items-center justify-between p-4">
              <span>ID</span>
              <span className="text-muted-foreground">{network.id}</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Name</span>
              {editMode === 'name' ? (
                <Input
                  id="name"
                  className="h-6 max-w-48"
                  value={form.data.name}
                  onChange={(e) => form.setData('name', e.target.value)}
                  onKeyDown={handleEnterKey}
                  autoFocus
                />
              ) : (
                <button type="button" className="text-muted-foreground cursor-pointer underline" onClick={() => setEditMode('name')}>
                  {form.data.name}
                </button>
              )}
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Type</span>
              <Badge variant={network.type_color}>{network.type}</Badge>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Status</span>
              <Badge variant={network.status_color}>{network.status}</Badge>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>CIDR</span>
              <span className="text-muted-foreground">{network.cidr ?? '—'}</span>
            </div>
            {!isProvider && (
              <>
                <Separator />
                <div className="flex items-center justify-between p-4">
                  <span>Address pool</span>
                  <span className="text-muted-foreground">{network.addressing_pool}</span>
                </div>
                <Separator />
                <div className="flex items-center justify-between p-4">
                  <span>Listen port</span>
                  <span className="text-muted-foreground">{network.port ?? '—'}</span>
                </div>
              </>
            )}
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Created at</span>
              <span className="text-muted-foreground">{formatDateString(network.created_at)}</span>
            </div>
          </CardContent>
        </Card>

        <Card className="border-destructive/30 overflow-hidden">
          <CardHeader>
            <CardTitle>Delete network</CardTitle>
            <CardDescription>Tear this network down on all of its servers and remove it. This action cannot be undone.</CardDescription>
          </CardHeader>
          <CardContent className="bg-background">
            <div className="p-4">
              <Button
                variant="destructive"
                onClick={() =>
                  dialog.confirm.open({
                    title: `Delete network [${network.name}]`,
                    description: `Are you sure you want to delete ${network.name}? This tears the network down on all of its servers.`,
                    variant: 'destructive',
                    confirmLabel: 'Delete',
                    method: 'delete',
                    url: route('networks.destroy', { network: network.id }),
                  })
                }
              >
                Delete network
              </Button>
            </div>
          </CardContent>
        </Card>
      </Container>
    </NetworkLayout>
  );
}
