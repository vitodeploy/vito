import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import type { VisitOptions } from '@inertiajs/core';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import InputError from '@/components/ui/input-error';

export type ConfirmationDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  confirmLabel?: string;
  variant?: 'default' | 'destructive';
  method: 'delete' | 'post' | 'patch' | 'put';
  url: string;
  data?: Record<string, string | number | boolean | null>;
  options?: VisitOptions;
  onSuccess?: () => void;
};

export default function ConfirmationDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel = 'Confirm',
  variant = 'default',
  method,
  url,
  data,
  options,
  onSuccess,
}: ConfirmationDialogProps) {
  const form = useForm<Record<string, string | number | boolean | null>>(data ?? {});

  const submit = () => {
    const visitOptions: VisitOptions = {
      preserveScroll: true,
      ...options,
      onSuccess: () => {
        onOpenChange(false);
        onSuccess?.();
      },
    };
    if (method === 'delete') {
      form.delete(url, visitOptions);
    } else if (method === 'post') {
      form.post(url, visitOptions);
    } else if (method === 'patch') {
      form.patch(url, visitOptions);
    } else {
      form.put(url, visitOptions);
    }
  };

  const errors = Object.values(form.errors) as string[];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription className="sr-only">{description ?? title}</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          {description && <p>{description}</p>}
          {errors.map((error) => (
            <InputError key={error} message={error} />
          ))}
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={variant} disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
