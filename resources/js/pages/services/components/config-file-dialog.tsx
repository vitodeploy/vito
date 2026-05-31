import { ConfigPath, Service } from '@/types/service';
import { FormEvent, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { useInputFocus } from '@/stores/useInputFocus';

export default function ServiceConfigFileDialog({
  open,
  onOpenChange,
  service,
  configPath,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  service: Service;
  configPath: ConfigPath;
}) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const form = useForm<{
    content: string;
    config_name: string;
  }>({
    content: '',
    config_name: configPath.name,
  });

  useEffect(() => {
    setFocused(open);
    return () => setFocused(false);
  }, [open, setFocused]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('services.config.update', { server: service.server_id, service: service.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  const query = useQuery({
    queryKey: ['services.config', service.server_id, service.id, configPath.name],
    queryFn: async () => {
      const response = await axios.get(
        route('services.config', {
          server: service.server_id,
          service: service.id,
          config_name: configPath.name,
        }),
      );
      if (typeof response.data?.content === 'string') {
        form.setData('content', response.data.content);
      }
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-5xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>Edit {configPath.name}</SheetTitle>
          <SheetDescription className="sr-only">
            You can edit the {configPath.name} file for this service. Make sure to save your changes.
          </SheetDescription>
        </SheetHeader>
        <Form id="update-config-form" className="h-full" onSubmit={submit}>
          {query.isSuccess ? (
            <Editor
              defaultLanguage="ini"
              value={form.data.content}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('content', value ?? '')}
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
            <Button form="update-config-form" type="submit" disabled={form.processing || query.isLoading} className="ml-2">
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
