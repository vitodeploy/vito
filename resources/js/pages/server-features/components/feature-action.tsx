import { Server, ServerFeatureAction } from '@/types/server';
import { FormEvent } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';

type FieldValue = string | number | boolean | string[] | null | undefined;
import { LoaderCircleIcon } from 'lucide-react';

export default function FeatureAction({
  open,
  onOpenChange,
  server,
  featureId,
  actionId,
  action,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  server: Server;
  featureId: string;
  actionId: string;
  action: ServerFeatureAction;
}) {
  const form = useForm<Record<string, FieldValue>>({});

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(
      route('server-features.action', {
        server: server.id,
        feature: featureId,
        action: actionId,
      }),
      {
        onSuccess: () => onOpenChange(false),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{action.label}</DialogTitle>
          <DialogDescription className="sr-only">action {action.label}</DialogDescription>
        </DialogHeader>
        <Form id="action-form" onSubmit={submit} className="p-4">
          <FormFields>
            {action.form?.map((field: DynamicFieldConfig) => (
              <DynamicField
                key={`field-${field.name}`}
                value={form.data[field.name] as string | number | boolean | string[] | undefined}
                onChange={(value) => form.setData(field.name, value as FieldValue)}
                config={field}
                error={form.errors[field.name] as string | undefined}
              />
            ))}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="action-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {action.label}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
