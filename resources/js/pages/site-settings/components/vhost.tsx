import React, { FormEvent, ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { registerCaddyLanguage, registerNginxLanguage } from '@/lib/editor';
import { useAppearance } from '@/hooks/use-appearance';
import { Site } from '@/types/site';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { StatusRipple } from '@/components/status-ripple';

export default function VHost({ site, children }: { site: Site; children: ReactNode }) {
  const { getActualAppearance } = useAppearance();
  const [open, setOpen] = useState(false);
  const form = useForm<{
    vhost: string;
  }>({
    vhost: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('site-settings.update-vhost', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  const query = useQuery({
    queryKey: ['site-settings.vhost', site.server_id, site.id],
    queryFn: async () => {
      const response = await axios.get(
        route('site-settings.vhost', {
          server: site.server_id,
          site: site.id,
        }),
      );
      if (response.data?.vhost) {
        form.setData('vhost', response.data.vhost);
      }
      return response.data;
    },
    retry: false,
    enabled: open,
  });

  const monaco = useMonaco();
  registerNginxLanguage(monaco);
  registerCaddyLanguage(monaco);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>Edit virtual host file</SheetTitle>
          <SheetDescription className="sr-only">Edit virtual host file.</SheetDescription>
        </SheetHeader>
        <Form id="update-vhost-form" className="h-full" onSubmit={submit}>
          {query.isSuccess ? (
            <Editor
              defaultLanguage={site.webserver}
              defaultValue={query.data.vhost}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('vhost', value ?? '')}
              options={{
                fontSize: 15,
              }}
            />
          ) : (
            <Skeleton className="h-full w-full rounded-none" />
          )}
          {/*make alert center with absolute position*/}
          <div className="absolute! right-0 bottom-[80px] left-0 z-10 mx-auto max-w-5xl px-6">
            <Alert variant="destructive">
              <AlertDescription className="flex items-center gap-2">
                <StatusRipple variant="destructive" />
                <p>Nginx vhost file will get reset if you generate or modify SSLs, Aliases, or create/delete site redirects.</p>
              </AlertDescription>
            </Alert>
          </div>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="update-vhost-form" disabled={form.processing || query.isLoading} onClick={submit} className="ml-2">
              {(form.processing || query.isLoading) && <LoaderCircleIcon className="animate-spin" />}
              Save
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
