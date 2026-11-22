import { cn } from "@/lib/cn";
import type { SelectHTMLAttributes } from "react";

type Props = SelectHTMLAttributes<HTMLSelectElement> & {
  label?: string;
};

export function Select({ label, className, children, ...props }: Props) {
  return (
    <label className={cn("flex flex-col gap-1.5 text-sm", className)}>
      {label ? <span className="font-medium text-foreground">{label}</span> : null}
      <select
        className="w-full rounded-lg border border-border bg-white px-3 py-2 text-foreground outline-none focus:border-accent focus:ring-2 focus:ring-accent/30"
        {...props}
      >
        {children}
      </select>
    </label>
  );
}