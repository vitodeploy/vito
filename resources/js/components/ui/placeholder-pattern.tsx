import { useId } from 'react';

interface PlaceholderPatternProps {
  className?: string;
}

export function PlaceholderPattern({ className }: PlaceholderPatternProps) {
  const patternId = useId();

  return (
    <svg className={className} fill="none">
      <defs>
        <pattern id={patternId} x="0" y="0" width="10" height="10" patternUnits="userSpaceOnUse">
          <path d="M-3 13 15-5M-5 5l18-18M-1 21 17 3"></path>
        </pattern>
      </defs>
      <rect stroke="none" fill={`url(#${patternId})`} width="100%" height="100%"></rect>
    </svg>
  );
}
