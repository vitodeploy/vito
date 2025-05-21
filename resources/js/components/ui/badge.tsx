import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';

import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-auto',
  {
    variants: {
      variant: {
        default: 'border border-primary/40 dark:border-primary bg-primary/10 dark:bg-primary/20 text-primary/90 dark:text-foreground/90',
        success: 'border border-success/40 dark:border-success/60 bg-success/10 dark:bg-success/20 text-success/90 dark:text-foreground/90',
        info: 'border border-info/40 dark:border-info/60 bg-info/10 dark:bg-info/20 text-info/90 dark:text-foreground/90',
        warning: 'border border-warning/40 dark:border-warning/60 bg-warning/10 dark:bg-warning/20 text-warning/90 dark:text-foreground/90',
        danger:
          'border border-destructive/40 dark:border-destructive/60 bg-destructive/10 dark:bg-destructive/20 text-destructive/90 dark:text-foreground/90',
        destructive:
          'border border-destructive/40 dark:border-destructive/60 bg-destructive/10 dark:bg-destructive/20 text-destructive/90 dark:text-foreground/90',
        gray: 'border border-gray/40 dark:border-gray/60 bg-gray/10 dark:bg-gray/20 text-gray/90 dark:text-foreground/90',
        outline: 'text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : 'span';

  return <Comp data-slot="badge" className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
