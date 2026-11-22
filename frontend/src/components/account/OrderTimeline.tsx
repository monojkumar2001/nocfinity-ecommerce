import { cn } from "@/lib/cn";
import type { OrderStatusStep } from "@/lib/types";

export function OrderTimeline({ steps }: { steps: OrderStatusStep[] }) {
  return (
    <ol className="space-y-3">
      {steps.map((step, index) => {
        const done = step.state === "done" || step.state === "current";
        const cancelled = step.state === "cancelled";

        return (
          <li key={step.key} className="flex items-start gap-3">
            <div className="flex flex-col items-center">
              <span
                className={cn(
                  "mt-0.5 flex h-3.5 w-3.5 rounded-full border-2",
                  cancelled
                    ? "border-rose-500 bg-rose-500"
                    : done
                      ? "border-black bg-black"
                      : "border-black/20 bg-white",
                )}
              />
              {index < steps.length - 1 ? (
                <span className={cn("mt-1 w-px flex-1", done ? "bg-black/40" : "bg-black/10")} style={{ minHeight: 18 }} />
              ) : null}
            </div>
            <div className="-mt-0.5">
              <p
                className={cn(
                  "text-sm font-medium",
                  cancelled ? "text-rose-700" : done ? "text-black" : "text-black/40",
                )}
              >
                {step.label}
              </p>
              {step.state === "current" ? (
                <p className="text-xs text-black/50">Current status</p>
              ) : null}
            </div>
          </li>
        );
      })}
    </ol>
  );
}
