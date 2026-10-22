import { ComponentType, ReactNode, useEffect } from 'react';
import { InertiaFormProps } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

export function Section({
  title,
  description,
  action,
  footer,
  destructive,
  children,
}: {
  title: ReactNode;
  description?: ReactNode;
  action?: ReactNode;
  footer?: ReactNode;
  destructive?: boolean;
  children?: ReactNode;
}) {
  return (
    <Card className={cn('overflow-hidden', destructive && 'border-destructive/40')}>
      <CardHeader className="flex-row items-center justify-between gap-4 last:border-b-0">
        <div className="flex min-w-0 flex-col gap-1.5">
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </div>
        {action && <div className="flex shrink-0 items-center gap-2">{action}</div>}
      </CardHeader>
      {children && <CardContent className="bg-background divide-y">{children}</CardContent>}
      {footer && <CardFooter className="justify-end gap-2">{footer}</CardFooter>}
    </Card>
  );
}

export function Row({
  label,
  description,
  children,
  className,
}: {
  label: ReactNode;
  description?: ReactNode;
  children?: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn('flex min-h-15 items-center justify-between gap-4 px-4 py-3 text-sm', className)}>
      <div className="flex min-w-0 flex-1 flex-col gap-0.5">
        <span className="truncate font-medium">{label}</span>
        {description && <span className="text-muted-foreground">{description}</span>}
      </div>
      {children !== undefined && (
        <div className="text-muted-foreground flex max-w-[60%] min-w-0 items-center justify-end gap-2 [&>span]:truncate">{children}</div>
      )}
    </div>
  );
}

export function Field({
  id,
  label,
  description,
  error,
  inline,
  children,
}: {
  id: string;
  label: string;
  description?: ReactNode;
  error?: string;
  inline?: boolean;
  children: ReactNode;
}) {
  if (!inline) {
    return (
      <FormField>
        <Label htmlFor={id}>{label}</Label>
        {children}
        {description && <p className="text-muted-foreground text-xs">{description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  return (
    <div className="grid min-h-15 gap-3 px-4 py-3 text-sm sm:grid-cols-[minmax(0,1fr)_minmax(0,20rem)] sm:items-center sm:gap-8">
      <div className="flex min-w-0 flex-col gap-0.5">
        <Label htmlFor={id}>{label}</Label>
        {description && <p className="text-muted-foreground">{description}</p>}
      </div>
      <div className="flex min-w-0 flex-col gap-2">
        {children}
        <InputError message={error} />
      </div>
    </div>
  );
}

export function SwitchField({
  id,
  label,
  description,
  checked,
  onCheckedChange,
  disabled,
  error,
}: {
  id: string;
  label: string;
  description?: ReactNode;
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
  disabled?: boolean;
  error?: string;
}) {
  return (
    <FormField>
      <div className="flex items-center justify-between gap-4">
        <div className="flex flex-col gap-0.5">
          <Label htmlFor={id}>{label}</Label>
          {description && <p className="text-muted-foreground text-xs">{description}</p>}
        </div>
        <Switch id={id} checked={checked} disabled={disabled} onCheckedChange={onCheckedChange} />
      </div>
      <InputError message={error} />
    </FormField>
  );
}

/**
 * Keeps an untouched form in step with props refreshed by polling, so a later save never sends stale values.
 */
export function useSyncedDefaults<T extends Record<string, unknown>>(form: InertiaFormProps<T>, values: T): void {
  const { isDirty, setDefaults, setData } = form;
  const json = JSON.stringify(values);
  useEffect(() => {
    if (isDirty) return;
    const next = JSON.parse(json) as T;
    setDefaults(next);
    setData(next);
  }, [json, isDirty, setDefaults, setData]);
}

export function SaveActions({ form }: { form: { processing: boolean; isDirty: boolean; reset: () => void; clearErrors: () => void } }) {
  return (
    <>
      {form.isDirty && (
        <Button
          type="button"
          variant="ghost"
          onClick={() => {
            form.reset();
            form.clearErrors();
          }}
        >
          Discard
        </Button>
      )}
      <Button type="submit" disabled={form.processing || !form.isDirty}>
        {form.processing && <LoaderCircleIcon className="animate-spin" />}Save
      </Button>
    </>
  );
}

export function EmptyState({
  icon: Icon,
  title,
  description,
  action,
  className,
}: {
  icon: ComponentType<{ className?: string }>;
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn('flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center', className)}>
      <span className="bg-muted flex size-10 items-center justify-center rounded-lg">
        <Icon className="text-muted-foreground size-5" />
      </span>
      <div className="flex flex-col gap-1">
        <p className="font-medium">{title}</p>
        {description && <p className="text-muted-foreground max-w-sm text-sm">{description}</p>}
      </div>
      {action}
    </div>
  );
}
