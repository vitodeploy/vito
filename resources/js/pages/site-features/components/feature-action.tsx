import { Site, SiteFeatureAction } from '@/types/site';
import React, { FormEvent, ReactNode, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { LoaderCircleIcon } from 'lucide-react';

export default function FeatureAction({
  site,
  featureId,
  actionId,
  action,
  children,
}: {
  site: Site;
  featureId: string;
  actionId: string;
  action: SiteFeatureAction;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(
      route('site-features.action', {
        server: site.server_id,
        site: site.id,
        feature: featureId,
        action: actionId,
      }),
      {
        onSuccess: () => {
          setOpen(false);
          form.reset();
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{action.label}</DialogTitle>
          <DialogDescription className="sr-only">action {action.label}</DialogDescription>
        </DialogHeader>
        <Form id="action-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <p className="text-muted-foreground">
                You're performing action <b className="text-foreground">[{action.label}]</b> on site{' '}
                <b className="text-foreground">[{site.domain}]</b>
              </p>
            </FormField>
            {action.form?.map((field: DynamicFieldConfig) => (
              <DynamicField
                key={`field-${field.name}`}
                /*@ts-expect-error dynamic types*/
                value={form.data[field.name]}
                onChange={(value) => form.setData(field.name, value)}
                config={field}
                error={form.errors[field.name]}
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
