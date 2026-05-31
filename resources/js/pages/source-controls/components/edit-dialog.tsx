import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent, useMemo } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { useConfigs } from '@/stores/bootstrap-store';
import { SourceControl } from '@/types/source-control';

type SourceControlFieldValue = string | number | boolean | string[] | null | undefined;

export default function SourceControlEditDialog({
  open,
  onOpenChange,
  sourceControl,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  sourceControl: SourceControl;
}) {
  const isGithubApp = sourceControl.provider === 'github-app';
  const configs = useConfigs()!;
  const providerConfig = configs.source_control?.providers?.[sourceControl.provider];

  const editableFormFields = useMemo<DynamicFieldConfig[]>(() => {
    const editableFields = providerConfig?.editable_fields ?? [];
    return (providerConfig?.form ?? []).filter((f) => editableFields.includes(f.name));
  }, [providerConfig]);

  const form = useForm<Record<string, SourceControlFieldValue>>({
    name: sourceControl.name,
    global: sourceControl.global,
    ...Object.fromEntries(editableFormFields.map((f) => [f.name, (sourceControl[f.name] as SourceControlFieldValue) ?? f.default ?? null])),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('source-controls.update', sourceControl.id), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit {sourceControl.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit source control</DialogDescription>
        </DialogHeader>
        <Form id="edit-source-control-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input
                type="text"
                id="name"
                name="name"
                value={form.data.name as string}
                onChange={(e) => form.setData('name', e.target.value)}
                disabled={isGithubApp}
                readOnly={isGithubApp}
              />
              {isGithubApp && <p className="text-muted-foreground text-xs">The name is the GitHub organization and cannot be changed.</p>}
              <InputError message={form.errors.name as string | undefined} />
            </FormField>
            {editableFormFields.map((field) => (
              <DynamicField
                key={field.name}
                value={form.data[field.name] as string | number | boolean | string[] | undefined}
                onChange={(value) => form.setData(field.name, value)}
                config={field}
                error={form.errors[field.name] as string | undefined}
              />
            ))}
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox
                  id="global"
                  name="global"
                  checked={form.data.global as boolean}
                  onCheckedChange={(checked) => form.setData('global', checked === true)}
                />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global as string | undefined} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-source-control-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
