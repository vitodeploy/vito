import { ReactNode } from 'react';

export default function Heading({ title, description, badges }: { title: string; description?: string; badges?: ReactNode }) {
  const heading = <h2 className="text-xl font-semibold tracking-tight">{title}</h2>;

  return (
    <div className="space-y-0.5">
      {badges ? <div className="flex flex-wrap items-center gap-2">{heading}{badges}</div> : heading}
      {description && <p className="text-muted-foreground text-sm">{description}</p>}
    </div>
  );
}
