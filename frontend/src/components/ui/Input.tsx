import { cn } from "@/lib/cn";
import type { InputHTMLAttributes } from "react";

type Props = InputHTMLAttributes<HTMLInputElement> & {
  label?: string;
  error?: string;
};

export function Input({ label, error, className, id, ...props }: Props) {
  const inputId = id ?? label?.toLowerCase().replace(/\s+/g, "-");
  return (
    <label className={cn("flex flex-col gap-1.5 text-sm", className)}>
      {label ? <span className="font-medium text-foreground">{label}</span> : null}
      <input
        id={inputId}
        className={cn(
          "w-full rounded-lg border border-border bg-white px-3 py-2 text-foreground outline-none ring-accent/30 placeholder:text-muted-foreground focus:border-accent focus:ring-2",
          error && "border-red-500",
        )}
        {...props}
      />
      {error ? <span className="text-xs text-red-600">{error}</span> : null}
    </label>
  );
}