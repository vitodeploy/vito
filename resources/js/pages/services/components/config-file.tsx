import { ConfigPath, Service } from '@/types/service';
import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import { useInputFocus } from '@/stores/useInputFocus';

export default function ConfigFile({ service, configPath }: { service: Service; configPath: ConfigPath }) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const [open, setOpen] = useState(false);
  const form = useForm<{
    content: string;
    config_name: string;
  }>({
    content: '',
    config_name: configPath.name,
  });

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    setFocused(isOpen);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('services.config.update', { server: service.server_id, service: service.id }), {
      onSuccess: () => {
        handleOpenChange(false);
      },
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
      if (response.data?.content) {
        form.setData('content', response.data.content);
      }
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit {configPath.name}</DropdownMenuItem>
      </SheetTrigger>
      <SheetContent className="sm:max-w-5xl">
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
              value={query.data.content}
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
            <Button form="update-config-form" disabled={form.processing || query.isLoading} onClick={submit} className="ml-2">
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
