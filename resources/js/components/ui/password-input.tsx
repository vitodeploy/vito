import * as React from 'react';
import { EyeIcon, EyeOffIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import { useInputFocus } from '@/stores/useInputFocus';

type PasswordInputProps = Omit<React.ComponentProps<'input'>, 'type'>;

const PasswordInput = React.forwardRef<HTMLInputElement, PasswordInputProps>(({ className, ...props }, ref) => {
  const [showPassword, setShowPassword] = React.useState(false);
  const setFocused = useInputFocus((state) => state.setFocused);

  return (
    <div className="relative">
      <input
        type={showPassword ? 'text' : 'password'}
        data-slot="input"
        className={cn(
          'file:text-foreground placeholder:text-muted-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
          'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
          'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
          'pr-10',
          className,
        )}
        ref={ref}
        onFocus={() => setFocused(true)}
        onBlur={() => setFocused(false)}
        {...props}
      />
      <button
        type="button"
        className="text-muted-foreground hover:text-foreground absolute top-0 right-0 flex h-9 w-9 items-center justify-center disabled:pointer-events-none disabled:opacity-50"
        onClick={() => setShowPassword((prev) => !prev)}
        disabled={props.disabled}
        aria-label={showPassword ? 'Hide password' : 'Show password'}
        aria-pressed={showPassword}
      >
        {showPassword ? <EyeOffIcon className="size-4" aria-hidden="true" /> : <EyeIcon className="size-4" aria-hidden="true" />}
      </button>
    </div>
  );
});

PasswordInput.displayName = 'PasswordInput';

export { PasswordInput };
