import { Service } from '@/types/service';
import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { useConfigs } from '@/stores/bootstrap-store';

export default function PhpExtensionsDialog({
  open,
  onOpenChange,
  service,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  service: Service;
}) {
  const configs = useConfigs()!;
  const form = useForm<{
    extension: string;
    version: string;
  }>({
    extension: '',
    version: service.version,
  });
  const [, php] = Object.entries(configs.service.services).filter(([key]) => key === 'php')[0] ?? [];

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('php.install-extension', { server: service.server_id, service: service.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  if (!php) {
    return null;
  }

  const availableExtensions = Array.isArray(service.type_data?.available_extensions)
    ? service.type_data.available_extensions
    : Array.isArray(php.data?.extensions)
      ? php.data.extensions
      : Object.values(service.type_data?.available_extensions || php.data?.extensions || {});

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Install extension</DialogTitle>
          <DialogDescription className="sr-only">Install php extension</DialogDescription>
        </DialogHeader>
        <Form id="install-extension-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="extension">Extension</Label>
              <Select value={form.data.extension} onValueChange={(value) => form.setData('extension', value)}>
                <SelectTrigger id="extension">
                  <SelectValue placeholder="Select an extension" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {availableExtensions.map((extension: string) => (
                      <SelectItem key={`extension-${extension}`} value={extension} disabled={service.type_data?.extensions?.includes(extension)}>
                        {extension} {service.type_data?.extensions?.includes(extension) && <span>(installed)</span>}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.extension} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="install-extension-form" type="submit" disabled={form.processing} className="ml-2">
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Install
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
