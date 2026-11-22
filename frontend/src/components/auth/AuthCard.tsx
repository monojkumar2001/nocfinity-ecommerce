export function AuthCard({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="mx-auto flex min-h-[60vh] max-w-md flex-col justify-center px-4 py-12">
      <h1 className="font-display text-center text-3xl font-bold">{title}</h1>
      {subtitle ? <p className="mt-2 text-center text-sm text-muted-foreground">{subtitle}</p> : null}
      <div className="mt-8 rounded-2xl border border-border bg-white p-6 shadow-sm">{children}</div>
    </div>
  );
}