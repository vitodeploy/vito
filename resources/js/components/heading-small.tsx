export default function HeadingSmall({ title, description }: { title: string; description?: string }) {
  return (
    <header>
      <h3 className="mb-0.5 text-base font-medium">{title}</h3>
      {description && <p className="text-muted-foreground text-sm">{description}</p>}
    </header>
  );
}
