export function SafeDescription({ text }: { text?: string | null }) {
  if (!text) return null;
  return (
    <p className="whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
      {text}
    </p>
  );
}