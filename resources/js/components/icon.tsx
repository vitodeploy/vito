import { cn } from '@/lib/utils';
import { type LucideProps } from 'lucide-react';
import { type ComponentType } from 'react';

interface IconProps extends Omit<LucideProps, 'ref'> {
  iconNode: ComponentType<LucideProps>;
}

export function Icon({ iconNode: IconComponent, className, ...props }: IconProps) {
  return <IconComponent className={cn('h-4 w-4', className)} {...props} />;
}
