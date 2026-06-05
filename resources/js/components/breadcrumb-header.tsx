import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';
import { type PropsWithChildren } from 'react';

export function BreadcrumbHeader({ breadcrumbs, children, className }: PropsWithChildren<{ breadcrumbs: BreadcrumbItem[]; className?: string }>) {
  const parent = breadcrumbs.length > 1 ? breadcrumbs[breadcrumbs.length - 2] : undefined;

  return (
    <div className={cn('flex flex-col items-start gap-4', className)}>
      {parent && (
        <Button variant="outline" size="sm" className="text-muted-foreground hover:text-foreground h-7 px-2.5" asChild>
          <Link href={parent.href}>
            <ArrowLeftIcon />
            {parent.title}
          </Link>
        </Button>
      )}
      {children}
    </div>
  );
}
