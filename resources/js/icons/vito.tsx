import React from 'react';
import { LucideProps } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

export const VitoIcon = React.forwardRef<SVGSVGElement, LucideProps>(({ color = 'currentColor', strokeWidth = 30, className, ...rest }, ref) => {
  return <AppLogoIcon ref={ref} color={color} className={cn(className, 'rounded-xs')} strokeWidth={strokeWidth} {...rest} />;
});

export default VitoIcon;
