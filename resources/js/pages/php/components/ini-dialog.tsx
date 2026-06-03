import { Service } from '@/types/service';
import { FormEvent, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor, useMonaco } from '@monaco-editor/react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form } from '@/components/ui/form';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { registerIniLanguage } from '@/lib/editor';
import { useAppearance } from '@/hooks/use-appearance';
import { useInputFocus } from '@/stores/useInputFocus';

export default function PhpIniDialog({
  open,
  onOpenChange,
  service,
  type,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  service: Service;
  type: 'fpm' | 'cli';
}) {
  const { getActualAppearance } = useAppearance();
  const setFocused = useInputFocus((state) => state.setFocused);
  const form = useForm<{
    ini: string;
    type: 'fpm' | 'cli';
    version: string;
  }>({
    ini: '',
    type: type,
    version: service.version,
  });

  useEffect(() => {
    setFocused(open);
    return () => setFocused(false);
  }, [open, setFocused]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('php.ini.update', { server: service.server_id, service: service.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  const query = useQuery({
    queryKey: ['php.ini', service.server_id, service.id, type],
    queryFn: async () => {
      const response = await axios.get(
        route('php.ini', {
          server: service.server_id,
          service: service.id,
          version: service.version,
          type: type,
        }),
      );
      if (typeof response.data?.ini === 'string') {
        form.setData('ini', response.data.ini);
      }
      return response.data;
    },
    retry: false,
    enabled: open,
    refetchOnWindowFocus: false,
  });

  registerIniLanguage(useMonaco());

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-5xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>Edit {type} ini</SheetTitle>
          <SheetDescription className="sr-only">You can edit the {type} ini file for this service. Make sure to save your changes.</SheetDescription>
        </SheetHeader>
        <Form id="update-ini-form" className="h-full" onSubmit={submit}>
          {query.isSuccess ? (
            <Editor
              defaultLanguage="ini"
              value={form.data.ini}
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('ini', value ?? '')}
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
            <Button form="update-ini-form" type="submit" disabled={form.processing || query.isLoading} className="ml-2">
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
