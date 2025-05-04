import React from 'react';
import { cn } from '@/lib/utils';

export function Form({ className, children, ...props }: React.ComponentProps<'form'>) {
  return (
    <form {...props} className={cn('flex w-full flex-col gap-6', className)}>
      {children}
    </form>
  );
}

export function FormFields({ className, children, ...props }: React.ComponentProps<'div'>) {
  return (
    <div className={cn('grid gap-6', className)} {...props}>
      {children}
    </div>
  );
}

export function FormField({ className, children, ...props }: React.ComponentProps<'div'>) {
  return (
    <div className={cn('grid gap-2', className)} {...props}>
      {children}
    </div>
  );
}
