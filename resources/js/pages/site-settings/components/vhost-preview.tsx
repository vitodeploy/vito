import React, { ReactNode, useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { registerCaddyLanguage, registerNginxLanguage } from '@/lib/editor';
import { useAppearance } from '@/hooks/use-appearance';
import { Site } from '@/types/site';

export default function VHostPreview({ site, children }: { site: Site; children: ReactNode }) {
  const { getActualAppearance } = useAppearance();
  const [open, setOpen] = useState(false);

  const query = useQuery({
    queryKey: ['site-settings.vhost', site.server_id, site.id],
    queryFn: async () => {
      const response = await axios.get(
        route('site-settings.vhost', {
          server: site.server_id,
          site: site.id,
        }),
      );
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  const monaco = useMonaco();

  useEffect(() => {
    if (monaco) {
      registerNginxLanguage(monaco);
      registerCaddyLanguage(monaco);
    }
  }, [monaco]);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>Generated VHost</SheetTitle>
          <SheetDescription className="sr-only">Read-only view of the deployed vhost config.</SheetDescription>
        </SheetHeader>
        <div className="h-full">
          {query.isSuccess ? (
            <Editor
              defaultLanguage={site.webserver}
              value={query.data.vhost}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              options={{
                fontSize: 15,
                readOnly: true,
                domReadOnly: true,
              }}
            />
          ) : (
            <Skeleton className="h-full w-full rounded-none" />
          )}
        </div>
        <SheetFooter>
          <SheetClose asChild>
            <Button variant="outline">Close</Button>
          </SheetClose>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
