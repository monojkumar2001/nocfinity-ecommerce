import { cn } from "@/lib/cn";
import type { ButtonHTMLAttributes } from "react";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "ghost" | "danger" | "outline";
  size?: "sm" | "md" | "lg";
};

export function Button({
  className,
  variant = "primary",
  size = "md",
  ...props
}: Props) {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center gap-2 font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 btn-pill",
        variant === "primary" && "bg-black text-white hover:bg-black/80",
        variant === "secondary" && "bg-white text-black hover:bg-black/5",
        variant === "outline" && "border border-black/10 bg-white text-black hover:bg-black/5",
        variant === "ghost" && "text-black hover:bg-black/5",
        variant === "danger" && "bg-sale text-white hover:bg-red-600",
        size === "sm" && "px-4 py-2 text-sm",
        size === "md" && "px-6 py-3 text-sm",
        size === "lg" && "min-h-[52px] px-8 py-4 text-base",
        className,
      )}
      {...props}
    />
  );
}
