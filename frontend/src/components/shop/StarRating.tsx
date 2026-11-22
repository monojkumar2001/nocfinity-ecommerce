import { cn } from "@/lib/cn";

/** Deterministic pseudo-rating from product id (4.0–5.0) for Figma-style cards */
export function ratingFromId(id: number): number {
  return Math.round((4 + ((id * 7) % 11) / 10) * 10) / 10;
}

export function StarRating({
  rating,
  className,
}: {
  rating: number;
  className?: string;
}) {
  const full = Math.floor(rating);
  const half = rating - full >= 0.5;

  return (
    <div className={cn("flex items-center gap-1", className)}>
      <div className="flex items-center gap-0.5" aria-label={`${rating} out of 5`}>
        {Array.from({ length: 5 }).map((_, i) => {
          const filled = i < full || (i === full && half);
          return (
            <svg
              key={i}
              width="16"
              height="16"
              viewBox="0 0 24 24"
              className={filled ? "text-star" : "text-black/15"}
              fill="currentColor"
              aria-hidden
            >
              <path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z" />
            </svg>
          );
        })}
      </div>
      <span className="text-sm text-black/60">
        {rating}/<span className="text-black">5</span>
      </span>
    </div>
  );
}
