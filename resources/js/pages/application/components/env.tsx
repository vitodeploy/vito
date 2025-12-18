import React, { FormEvent, ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, RefreshCwIcon } from 'lucide-react';
import { registerDotEnvLanguage } from '@/lib/editor';
import { Site } from '@/types/site';
import { useAppearance } from '@/hooks/use-appearance';
import { Input } from '@/components/ui/input';
import { useInputFocus } from '@/stores/useInputFocus';

export default function Env({ site, children }: { site: Site; children: ReactNode }) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);
  const form = useForm<{
    env: string;
    path: string;
  }>({
    env: '',
    path: site.type_data.env_path || `${site.path}/.env`,
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('application.update-env', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        handleOpenChange(false);
      },
    });
  };

  const query = useQuery({
    queryKey: ['application.env', site.server_id, site.id],
    queryFn: async () => {
      const response = await axios.get(
        route('application.env', {
          server: site.server_id,
          site: site.id,
          env: form.data.path,
        }),
      );
      if (response.data?.env) {
        form.setData('env', response.data.env);
      }
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  registerDotEnvLanguage(useMonaco());

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle className="flex items-center gap-4">
            <Input
              name="path"
              value={form.data.path}
              onChange={(e) => form.setData('path', e.target.value)}
              autoFocus={false}
              className="max-w-[80%]"
            />
            <Button variant="outline" size="icon" onClick={() => query.refetch()} disabled={query.isFetching}>
              <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
            </Button>
          </SheetTitle>
          <SheetDescription>Site path: {site.path}</SheetDescription>
        </SheetHeader>
        <Form id="update-env-form" className="h-full" onSubmit={submit}>
          {query.isSuccess ? (
            <Editor
              value={form.data.env}
              defaultLanguage="dotenv"
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('env', value ?? '')}
              options={{
                fontSize: 15,
              }}
            />
          ) : (
            <Skeleton className="h-full w-full rounded-none" />
          )}
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="update-env-form" disabled={form.processing || query.isLoading} onClick={submit} className="ml-2">
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
