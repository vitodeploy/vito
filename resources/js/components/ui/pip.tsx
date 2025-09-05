import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';

import { cn } from '@/lib/utils';

const pipVariants = cva(
  'rounded-full transition-colors self-stretch',
  {
    variants: {
      variant: {
        active: 'bg-green-500',
        inactive: 'bg-gray-400',
        disabled: 'bg-red-500',
      },
      size: {
        default: 'w-2',
        sm: 'w-3',
        md: 'w-4',
        lg: 'w-5',
      },
    },
    defaultVariants: {
      variant: 'inactive',
      size: 'default',
    },
  },
);

interface PipProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof pipVariants> {}

const Pip = React.forwardRef<HTMLDivElement, PipProps>(
  ({ className, variant, size, ...props }, ref) => {
    return (
      <div
        ref={ref}
        data-slot="pip"
        className={cn(pipVariants({ variant, size, className }))}
        {...props}
      />
    );
  }
);
Pip.displayName = 'Pip';

export { Pip };
