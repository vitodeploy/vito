import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Application, Environment, EnvironmentVariable } from '@/types/application';

export default function VariableForm({ open, onOpenChange, application, environment, variable }: DialogControlProps & {
  application: Application;
  environment: Environment;
  variable?: EnvironmentVariable;
}) {
  const form = useForm<{ key: string; value: string | null; is_secret: boolean }>({
    key: variable?.key ?? '',
    value: variable?.is_secret ? null : variable?.value ?? '',
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
            <FormField>
              {variable?.is_secret && <div className="flex items-center gap-2">
                <Checkbox id="variable-replace" checked={form.data.value !== null} disabled={form.processing} onCheckedChange={(checked) => form.setData({ ...form.data, value: checked === true ? '' : null, is_secret: checked === true ? form.data.is_secret : true })} />
                <Label htmlFor="variable-replace">Replace stored secret</Label>
              </div>}
              <Label htmlFor="variable-value">Value</Label>
              <Textarea id="variable-value" autoComplete="off" spellCheck={false} rows={5} disabled={form.processing || (variable?.is_secret && form.data.value === null)} value={form.data.value ?? ''} onChange={(e) => form.setData('value', e.target.value)} />
              {variable?.is_secret && <p className="text-muted-foreground text-sm">{form.data.value === null ? 'The stored secret will be kept.' : 'The replacement will be saved, even when empty.'}</p>}
              <InputError message={form.errors.value} />
            </FormField>
            <FormField>
              <div className="flex items-center gap-2">
                <Checkbox id="variable-secret" disabled={form.processing || (variable?.is_secret && form.data.value === null)} checked={form.data.is_secret} onCheckedChange={(checked) => form.setData('is_secret', checked === true)} />
                <Label htmlFor="variable-secret">Secret (hide the saved value)</Label>
              </div>
              <InputError message={form.errors.is_secret} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="variable-form" type="submit" disabled={form.processing}>{form.processing && <LoaderCircleIcon className="animate-spin" />}Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
