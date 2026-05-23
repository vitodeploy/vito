import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

type Accent = 'default' | 'warning' | 'destructive' | 'success';

interface Props {
  label: string;
  value: ReactNode;
  subtitle?: ReactNode;
  accent?: Accent;
}

const accentClasses: Record<Accent, string> = {
  default: '',
  warning: 'border-amber-500/40 bg-amber-500/5',
  destructive: 'border-destructive/40 bg-destructive/5',
  success: 'border-emerald-500/40 bg-emerald-500/5',
};

export function StatTile({ label, value, subtitle, accent = 'default' }: Props) {
  return (
    <Card className={cn('overflow-hidden', accentClasses[accent])}>
      <CardContent className="flex flex-col gap-1 p-4">
        <span className="text-muted-foreground text-xs tracking-wide uppercase">{label}</span>
        <span className="text-2xl font-semibold tabular-nums">{value}</span>
        {subtitle && <span className="text-muted-foreground text-xs">{subtitle}</span>}
      </CardContent>
    </Card>
  );
}
