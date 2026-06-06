import { RadioGroupItem } from '@/components/ui/radio-group';
import { cn } from '@/lib/utils';

export default function RadioCard({
  value,
  selected,
  title,
  description,
  onSelect,
}: {
  value: string;
  selected: boolean;
  title: string;
  description: string;
  onSelect: (value: string) => void;
}) {
  return (
    <label
      onClick={() => onSelect(value)}
      className={cn(
        'hover:bg-accent flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors',
        selected && 'border-primary bg-accent',
      )}
    >
      <RadioGroupItem value={value} className="mt-0.5" />
      <span className="flex flex-col gap-1">
        <span className="text-sm font-medium">{title}</span>
        <span className="text-muted-foreground text-sm">{description}</span>
      </span>
    </label>
  );
}
