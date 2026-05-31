import React, { FormEvent, ReactNode, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { EyeIcon, LoaderCircleIcon, RotateCcwIcon } from 'lucide-react';
import { registerCaddyLanguage, registerNginxLanguage } from '@/lib/editor';
import { useAppearance } from '@/hooks/use-appearance';
import { Site } from '@/types/site';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { StatusRipple } from '@/components/status-ripple';
import { useInputFocus } from '@/stores/useInputFocus';

export default function VHost({ site, children }: { site: Site; children: ReactNode }) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);
  const [showResetDialog, setShowResetDialog] = useState(false);
  const [resetting, setResetting] = useState(false);
  const [showPreviewDialog, setShowPreviewDialog] = useState(false);
  const [previewContent, setPreviewContent] = useState('');
  const [previewing, setPreviewing] = useState(false);
  const form = useForm<{
    template: string;
  }>({
    template: '',
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('site-settings.update-vhost-template', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        handleOpenChange(false);
      },
    });
  };

  const resetTemplate = () => {
    setResetting(true);
    router.post(
      route('site-settings.reset-vhost-template', { server: site.server_id, site: site.id }),
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          setShowResetDialog(false);
          handleOpenChange(false);
        },
        onFinish: () => {
          setResetting(false);
        },
      },
    );
  };

  const previewTemplate = () => {
    setPreviewing(true);
    axios
      .post(route('site-settings.vhost-preview', { server: site.server_id, site: site.id }), {
        template: form.data.template,
      })
      .then((response) => {
        setPreviewContent(response.data.vhost);
        setShowPreviewDialog(true);
      })
      .catch(() => {})
      .finally(() => {
        setPreviewing(false);
      });
  };

  const query = useQuery({
    queryKey: ['site-settings.vhost-template', site.server_id, site.id],
    queryFn: async () => {
      const response = await axios.get(
        route('site-settings.vhost-template', {
          server: site.server_id,
          site: site.id,
        }),
      );
      if (typeof response.data?.template === 'string') {
        form.setData('template', response.data.template);
      }
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
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>Edit webserver template</SheetTitle>
          <SheetDescription className="sr-only">Edit the Mustache template used to generate the vhost config.</SheetDescription>
        </SheetHeader>
        <Form id="update-vhost-form" className="h-full" onSubmit={submit}>
          {query.isSuccess ? (
            <Editor
              defaultLanguage={site.webserver}
              value={form.data.template}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('template', value ?? '')}
              options={{
                fontSize: 15,
              }}
            />
          ) : (
            <Skeleton className="h-full w-full rounded-none" />
          )}
          <div className="absolute! right-0 bottom-[80px] left-0 z-10 mx-auto max-w-5xl px-6">
            <Alert>
              <AlertDescription className="flex items-center gap-2">
                <StatusRipple variant="info" />
                <p>This is the Mustache template used to generate the vhost. Changes here persist across SSL, domain, and redirect updates.</p>
              </AlertDescription>
            </Alert>
          </div>
        </Form>
        <SheetFooter>
          <div className="flex w-full items-center justify-between">
            <Button variant="outline" onClick={() => setShowResetDialog(true)}>
              <RotateCcwIcon />
              Reset
            </Button>
            <div className="flex items-center gap-2">
              <Button variant="outline" onClick={previewTemplate} disabled={previewing || query.isLoading}>
                {previewing ? <LoaderCircleIcon className="animate-spin" /> : <EyeIcon />}
                Preview
              </Button>
              <Button form="update-vhost-form" disabled={form.processing || query.isLoading} onClick={submit}>
                {(form.processing || query.isLoading) && <LoaderCircleIcon className="animate-spin" />}
                Save
              </Button>
              <SheetClose asChild>
                <Button variant="outline">Cancel</Button>
              </SheetClose>
            </div>
          </div>
        </SheetFooter>
      </SheetContent>
      <Dialog open={showResetDialog} onOpenChange={setShowResetDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reset VHost template</DialogTitle>
            <DialogDescription>
              This will reset the template to the default and regenerate the VHost on the server. Any customizations will be lost.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowResetDialog(false)}>
              Cancel
            </Button>
            <Button variant="destructive" onClick={resetTemplate} disabled={resetting}>
              {resetting && <LoaderCircleIcon className="animate-spin" />}
              Reset
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={showPreviewDialog} onOpenChange={setShowPreviewDialog}>
        <DialogContent className="h-[80vh] sm:max-w-5xl">
          <DialogHeader>
            <DialogTitle>Template preview</DialogTitle>
            <DialogDescription className="sr-only">Preview of the generated vhost config from the current template.</DialogDescription>
          </DialogHeader>
          <div className="min-h-0 flex-1">
            <Editor
              defaultLanguage={site.webserver}
              value={previewContent}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              options={{
                fontSize: 15,
                readOnly: true,
                domReadOnly: true,
              }}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowPreviewDialog(false)}>
              Close
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Sheet>
  );
}
