import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Application, Environment, EnvironmentVariablePreview } from '@/types/application';
import { SharedData } from '@/types';

export default function ImportEnv({
  open,
  onOpenChange,
  application,
  environment,
}: DialogControlProps & {
  application: Application;
  environment: Environment;
}) {
  const form = useForm<{ content: string; secret_keys: string[] }>({ content: '', secret_keys: [] });
  const [preview, setPreview] = useState<EnvironmentVariablePreview[] | null>(null);
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/variables`;
  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (preview) {
      form.post(`${baseUrl}/import`, { onSuccess: () => onOpenChange(false) });
      return;
    }
    form.post(`${baseUrl}/preview`, {
      onSuccess: (page) => {
        const data = (page.props.flash as SharedData['flash'])?.data;
        if (
          !data ||
          typeof data !== 'object' ||
          !('variables' in data) ||
          !Array.isArray(data.variables) ||
          !data.variables.every(
            (variable): variable is EnvironmentVariablePreview =>
              !!variable && typeof variable === 'object' && typeof variable.key === 'string' && typeof variable.is_secret === 'boolean',
          )
        ) {
          form.setError('content', 'Unable to preview the env file. Please try again.');
          return;
        }
        setPreview(data.variables);
        form.setData(
          'secret_keys',
          data.variables.filter((variable) => variable.is_secret).map((variable) => variable.key),
        );
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Import .env</DialogTitle>
          <DialogDescription>
            Existing keys are replaced. Other variables are kept. Review which values should be secret before importing.
          </DialogDescription>
        </DialogHeader>
        <Form id="import-env-form" className="p-4" onSubmit={submit}>
          {preview ? (
            <div className="flex max-h-80 flex-col gap-3 overflow-auto">
              {preview.length === 0 && <p className="text-muted-foreground text-sm">No variables found.</p>}
              {preview.map((variable) => (
                <div key={variable.key} className="flex items-center justify-between gap-4">
                  <span className="font-mono text-sm break-all">{variable.key}</span>
                  <div className="flex items-center gap-2">
                    <Checkbox
                      id={`import-secret-${variable.key}`}
                      checked={form.data.secret_keys.includes(variable.key)}
                      onCheckedChange={(checked) =>
                        form.setData(
                          'secret_keys',
                          checked === true ? [...form.data.secret_keys, variable.key] : form.data.secret_keys.filter((key) => key !== variable.key),
                        )
                      }
                    />
                    <Label htmlFor={`import-secret-${variable.key}`}>
                      Secret<span className="sr-only">: {variable.key}</span>
                    </Label>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <FormField>
              <Label htmlFor="import-env-content">Env file contents</Label>
              <Textarea
                id="import-env-content"
                autoComplete="off"
                spellCheck={false}
                rows={10}
                disabled={form.processing}
                value={form.data.content}
                onChange={(e) => form.setData('content', e.target.value)}
              />
            </FormField>
          )}
          <InputError message={form.errors.content} />
          <InputError message={form.errors.secret_keys} />
          {Object.entries(form.errors)
            .filter(([key]) => key !== 'content' && key !== 'secret_keys')
            .map(([key, error]) => (
              <InputError key={key} message={error} />
            ))}
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          {preview && (
            <Button
              variant="outline"
              disabled={form.processing}
              onClick={() => {
                setPreview(null);
                form.clearErrors();
              }}
            >
              Back
            </Button>
          )}
          <Button form="import-env-form" type="submit" disabled={form.processing || preview?.length === 0}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {preview ? 'Import variables' : 'Preview'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
