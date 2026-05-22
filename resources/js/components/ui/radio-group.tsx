import * as React from 'react';
import { CircleIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

type RadioGroupContextValue = {
  name: string;
  value: string;
  onValueChange: (value: string) => void;
  disabled?: boolean;
};

const RadioGroupContext = React.createContext<RadioGroupContextValue | null>(null);

type RadioGroupProps = Omit<React.ComponentProps<'div'>, 'onChange'> & {
  value: string;
  onValueChange: (value: string) => void;
  name?: string;
  disabled?: boolean;
};

function RadioGroup({ className, value, onValueChange, name, disabled, children, ...props }: RadioGroupProps) {
  const generatedName = React.useId();
  const groupName = name ?? generatedName;

  return (
    <RadioGroupContext.Provider value={{ name: groupName, value, onValueChange, disabled }}>
      <div role="radiogroup" data-slot="radio-group" className={cn('grid gap-3', className)} {...props}>
        {children}
      </div>
    </RadioGroupContext.Provider>
  );
}

type RadioGroupItemProps = Omit<React.ComponentProps<'button'>, 'type' | 'role' | 'value' | 'onClick'> & {
  value: string;
};

function RadioGroupItem({ className, value, disabled, ...props }: RadioGroupItemProps) {
  const ctx = React.useContext(RadioGroupContext);
  if (!ctx) {
    throw new Error('RadioGroupItem must be rendered inside a RadioGroup');
  }

  const checked = ctx.value === value;
  const isDisabled = disabled ?? ctx.disabled;

  return (
    <button
      type="button"
      role="radio"
      aria-checked={checked}
      data-state={checked ? 'checked' : 'unchecked'}
      data-slot="radio-group-item"
      disabled={isDisabled}
      onClick={() => ctx.onValueChange(value)}
      className={cn(
        'border-input bg-background text-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40 inline-flex size-4 shrink-0 items-center justify-center rounded-full border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    >
      {checked && <CircleIcon className="size-2 fill-current" />}
    </button>
  );
}

export { RadioGroup, RadioGroupItem };
