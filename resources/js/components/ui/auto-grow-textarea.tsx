import * as React from 'react';
import { cn } from '@/lib/utils';
import { useInputFocus } from '@/stores/useInputFocus';

type AutoGrowTextareaProps = React.ComponentProps<'textarea'>;

const MAX_HEIGHT = 200; // Maximum height in pixels before scrolling

const AutoGrowTextarea = React.forwardRef<HTMLTextAreaElement, AutoGrowTextareaProps>(({ className, value, onChange, ...props }, ref) => {
  const setFocused = useInputFocus((state) => state.setFocused);
  const textareaRef = React.useRef<HTMLTextAreaElement>(null);

  // Combine refs
  React.useImperativeHandle(ref, () => textareaRef.current as HTMLTextAreaElement);

  const adjustHeight = React.useCallback(() => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = 'auto';
      const newHeight = Math.min(textarea.scrollHeight, MAX_HEIGHT);
      textarea.style.height = `${newHeight}px`;
      textarea.style.overflowY = textarea.scrollHeight > MAX_HEIGHT ? 'auto' : 'hidden';
    }
  }, []);

  React.useEffect(() => {
    adjustHeight();
  }, [value, adjustHeight]);

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    onChange?.(e);
    adjustHeight();
  };

  return (
    <textarea
      ref={textareaRef}
      data-slot="textarea"
      value={value}
      onChange={handleChange}
      rows={1}
      className={cn(
        'border-input placeholder:text-muted-foreground dark:bg-input/30 flex min-h-9 w-full min-w-0 resize-none rounded-md border bg-transparent px-3 py-[5px] text-base leading-[24px] shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        className,
      )}
      onFocus={() => setFocused(true)}
      onBlur={() => setFocused(false)}
      {...props}
    />
  );
});

AutoGrowTextarea.displayName = 'AutoGrowTextarea';

export { AutoGrowTextarea };
