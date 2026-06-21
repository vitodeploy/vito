import { FormEvent } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';

type PhpSettingsForm = {
  max_upload_size: string;
  max_execution_time: string;
  memory_limit: string;
  max_input_vars: string;
};

function SettingField({
  id,
  label,
  hint,
  placeholder,
  min,
  value,
  error,
  onChange,
}: {
  id: keyof PhpSettingsForm;
  label: string;
  hint: string;
  placeholder: string;
  min: number;
  value: string;
  error?: string;
  onChange: (value: string) => void;
}) {
  return (
    <FormField>
      <Label htmlFor={id}>{label}</Label>
      <Input id={id} type="number" min={min} placeholder={placeholder} value={value} onChange={(e) => onChange(e.target.value)} />
      <p className="text-muted-foreground text-xs">{hint}</p>
      <InputError message={error} />
    </FormField>
  );
}

export default function PhpSettingsDialog({ open, onOpenChange, site }: { open: boolean; onOpenChange: (open: boolean) => void; site: Site }) {
  const form = useForm<PhpSettingsForm>({
    max_upload_size: site.php_settings.max_upload_size?.toString() ?? '',
    max_execution_time: site.php_settings.max_execution_time?.toString() ?? '',
    memory_limit: site.php_settings.memory_limit?.toString() ?? '',
    max_input_vars: site.php_settings.max_input_vars?.toString() ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.clearErrors();
    form.transform((data) => ({
      max_upload_size: data.max_upload_size === '' ? null : Number(data.max_upload_size),
      max_execution_time: data.max_execution_time === '' ? null : Number(data.max_execution_time),
      memory_limit: data.memory_limit === '' ? null : Number(data.memory_limit),
      max_input_vars: data.max_input_vars === '' ? null : Number(data.max_input_vars),
    }));
    form.patch(route('site-settings.update-php-settings', { server: site.server_id, site: site.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Configure PHP settings</DialogTitle>
          <DialogDescription>Per-site PHP runtime limits. Leave a field empty to use the server default.</DialogDescription>
        </DialogHeader>

        <Form id="php-settings-form" onSubmit={submit} className="p-4">
          <FormFields>
            <SettingField
              id="max_upload_size"
              label="Max upload size (MB)"
              placeholder="e.g. 1024"
              hint="Default: 2 MB (nginx caps at 1 MB)"
              min={1}
              value={form.data.max_upload_size}
              error={form.errors.max_upload_size}
              onChange={(value) => form.setData('max_upload_size', value)}
            />
            <SettingField
              id="max_execution_time"
              label="Max execution time (seconds)"
              placeholder="e.g. 120"
              hint="Default: 30s (nginx caps at 60s)"
              min={1}
              value={form.data.max_execution_time}
              error={form.errors.max_execution_time}
              onChange={(value) => form.setData('max_execution_time', value)}
            />
            <SettingField
              id="memory_limit"
              label="Memory limit (MB)"
              placeholder="e.g. 256"
              hint="Default: 128 MB"
              min={16}
              value={form.data.memory_limit}
              error={form.errors.memory_limit}
              onChange={(value) => form.setData('memory_limit', value)}
            />
            <SettingField
              id="max_input_vars"
              label="Max input vars"
              placeholder="e.g. 5000"
              hint="Default: 1000"
              min={100}
              value={form.data.max_input_vars}
              error={form.errors.max_input_vars}
              onChange={(value) => form.setData('max_input_vars', value)}
            />
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="php-settings-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
