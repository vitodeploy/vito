import { cn } from '@/lib/utils';

export default function AppLogoIconHtml({ className }: { className?: string }) {
  return (
    <div
      className={cn(
        'border-primary dark:bg-primary/60 from-primary to-primary/80 flex size-7 items-center justify-center rounded-md border bg-gradient-to-br font-sans text-2xl font-bold text-white! shadow-xs dark:from-transparent dark:to-transparent',
        className,
      )}
    >
      V
    </div>
  );
}
