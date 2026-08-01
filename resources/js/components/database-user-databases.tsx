import type { CellComponentProps } from '@forjedio/inertia-table-react';
import { Badge } from '@/components/ui/badge';

export function DatabaseUserDatabases({ value }: CellComponentProps) {
  const items = value !== null && typeof value === 'object' ? Object.values(value as Record<string, unknown>) : [];
  const databases = items.every((item) => typeof item === 'string') ? (items as string[]) : [];

  if (databases.length === 0) {
    return <span className="text-muted-foreground">-</span>;
  }

  return (
    <div className="flex flex-wrap items-center gap-1">
      {databases.map((database) => (
        <Badge key={database} variant="outline">
          {database}
        </Badge>
      ))}
    </div>
  );
}
