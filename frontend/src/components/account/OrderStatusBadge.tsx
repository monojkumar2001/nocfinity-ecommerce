import { cn } from "@/lib/cn";
import { formatStatus } from "@/lib/format";

const tones: Record<string, string> = {
  pending: "bg-amber-50 text-amber-800",
  paid: "bg-sky-50 text-sky-800",
  processing: "bg-indigo-50 text-indigo-800",
  shipped: "bg-violet-50 text-violet-800",
  delivered: "bg-emerald-50 text-emerald-800",
  cancelled: "bg-rose-50 text-rose-800",
  failed: "bg-rose-50 text-rose-800",
  succeeded: "bg-emerald-50 text-emerald-800",
};

export function OrderStatusBadge({ status }: { status: string }) {
  return (
    <span
      className={cn(
        "inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize",
        tones[status] ?? "bg-black/5 text-black/70",
      )}
    >
      {formatStatus(status)}
    </span>
  );
}
