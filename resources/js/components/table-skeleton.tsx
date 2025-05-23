import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

export function TableSkeleton({ modal }: { modal?: boolean }) {
  const extraClasses = modal && 'border-none shadow-none';

  return (
    <div className={cn('rounded-md border shadow-xs', extraClasses)}>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>
              <Skeleton className="h-3 w-[100px]" />
            </TableHead>
            <TableHead>
              <Skeleton className="h-3 w-[100px]" />
            </TableHead>
            <TableHead>
              <Skeleton className="h-3 w-[100px]" />
            </TableHead>
            <TableHead>
              <Skeleton className="h-3 w-[100px]" />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {[...Array(3)].map((_, i) => (
            <TableRow key={i}>
              <TableCell>
                <Skeleton className="h-5 w-[100px]" />
              </TableCell>
              <TableCell>
                <Skeleton className="h-5 w-[100px]" />
              </TableCell>
              <TableCell>
                <Skeleton className="h-5 w-[100px]" />
              </TableCell>
              <TableCell>
                <Skeleton className="h-5 w-[100px]" />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
