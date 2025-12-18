import React, { FormEvent, ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { registerBashLanguage } from '@/lib/editor';
import InputError from '@/components/ui/input-error';
import { useAppearance } from '@/hooks/use-appearance';
import { DeploymentScript as DeploymentScriptType } from '@/types/deployment-script';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { StatusRipple } from '@/components/status-ripple';
import { useInputFocus } from '@/stores/useInputFocus';

export default function DeploymentScript({
  site,
  script,
  children,
  description,
}: {
  site: Site;
  script: DeploymentScriptType;
  description?: string;
  children: ReactNode;
}) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);

  const [open, setOpen] = useState(false);
  const form = useForm<{
    script: string;
    restart_workers: boolean;
  }>({
    script: script.content,
    restart_workers: script.configs.restart_workers,
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('application.update-deployment-script', { server: site.server_id, site: site.id, deploymentScript: script.id }), {
      onSuccess: () => {
        handleOpenChange(false);
      },
    });
  };

  registerBashLanguage(useMonaco());

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle className="capitalize">{script.name} script</SheetTitle>
          <SheetDescription>{description || 'Update script'}</SheetDescription>
        </SheetHeader>
        <Form id="update-script-form" className="relative h-full flex-col gap-0" onSubmit={submit}>
          <div className="relative flex-1">
            <Editor
              defaultLanguage="bash"
              value={form.data.script}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('script', value ?? '')}
              options={{
                fontSize: 15,
              }}
            />
            <div className="absolute! right-0 bottom-4 left-0 z-10 mx-auto max-w-5xl px-4">
              <Alert>
                <AlertDescription className="flex items-center gap-2">
                  <StatusRipple variant="default" />
                  <p>Using `php` command in your script will use the PHP version of the site.</p>
                </AlertDescription>
              </Alert>
            </div>
          </div>
          {['default', 'pre-flight'].includes(script.name) && (
            <FormFields className="p-4">
              <FormField className="mb-0">
                <div className="flex items-center space-x-2">
                  <Switch
                    id="restart_workers"
                    checked={form.data.restart_workers}
                    onCheckedChange={(value) => form.setData('restart_workers', value)}
                  />
                  <Label htmlFor="restart_workers">Restart workers after deployment</Label>
                  <InputError message={form.errors.restart_workers} />
                </div>
              </FormField>
            </FormFields>
          )}
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="update-script-form" disabled={form.processing} onClick={submit}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Save
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
            <InputError message={form.errors.script} />
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
