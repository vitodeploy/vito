import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Application, Environment, EnvironmentVariable } from '@/types/application';
import { SwitchField } from './section';

export default function VariableForm({
  open,
  onOpenChange,
  application,
  environment,
  variable,
}: DialogControlProps & {
  application: Application;
  environment: Environment;
  variable?: EnvironmentVariable;
}) {
  const form = useForm<{ key: string; value: string | null; is_secret: boolean }>({
    key: variable?.key ?? '',
    value: variable?.is_secret ? null : (variable?.value ?? ''),
    is_secret: variable?.is_secret ?? false,
  });
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/variables`;
  const submit = (e: FormEvent) => {
    e.preventDefault();
    const options = { onSuccess: () => onOpenChange(false) };
    if (variable) {
      form.put(`${baseUrl}/${variable.id}`, options);
    } else {
      form.post(baseUrl, options);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{variable ? 'Edit variable' : 'Add variable'}</DialogTitle>
          <DialogDescription>Changes apply to every component in this environment on the next deploy.</DialogDescription>
        </DialogHeader>
        <Form id="variable-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="variable-key">Key</Label>
              <Input id="variable-key" autoComplete="off" value={form.data.key} onChange={(e) => form.setData('key', e.target.value)} />
              <InputError message={form.errors.key} />
            </FormField>
            {variable?.is_secret && (
              <SwitchField
                id="variable-replace"
                label="Replace stored secret"
                description={form.data.value === null ? 'The stored secret will be kept.' : 'The replacement will be saved, even when empty.'}
                checked={form.data.value !== null}
                disabled={form.processing}
                onCheckedChange={(checked) =>
                  form.setData({ ...form.data, value: checked ? '' : null, is_secret: checked ? form.data.is_secret : true })
                }
              />
            )}
            <FormField>
              <Label htmlFor="variable-value">Value</Label>
              <Textarea
                id="variable-value"
                autoComplete="off"
                spellCheck={false}
                rows={5}
                disabled={form.processing || (variable?.is_secret && form.data.value === null)}
                value={form.data.value ?? ''}
                onChange={(e) => form.setData('value', e.target.value)}
              />
              <InputError message={form.errors.value} />
            </FormField>
            <SwitchField
              id="variable-secret"
              label="Secret"
              description="The saved value can't be viewed again."
              checked={form.data.is_secret}
              disabled={form.processing || (variable?.is_secret && form.data.value === null)}
              onCheckedChange={(checked) => form.setData('is_secret', checked)}
              error={form.errors.is_secret}
            />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="variable-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
