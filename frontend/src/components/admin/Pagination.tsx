"use client";

import { Button } from "@/components/ui/Button";
import type { PaginationMeta } from "@/lib/types";

export function Pagination({
  meta,
  onPageChange,
}: {
  meta?: PaginationMeta | null;
  onPageChange: (page: number) => void;
}) {
  if (!meta || meta.last_page <= 1) return null;

  return (
    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
      <p>
        Page {meta.current_page} of {meta.last_page} · {meta.total} total
      </p>
      <div className="flex gap-2">
        <Button
          type="button"
          size="sm"
          variant="secondary"
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
        >
          Previous
        </Button>
        <Button
          type="button"
          size="sm"
          variant="secondary"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
        >
          Next
        </Button>
      </div>
    </div>
  );
}
