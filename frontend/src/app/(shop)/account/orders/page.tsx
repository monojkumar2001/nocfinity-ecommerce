"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Order, PaginationMeta } from "@/lib/types";
import { formatCurrency, formatDate } from "@/lib/format";
import { OrderStatusBadge } from "@/components/account/OrderStatusBadge";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

export default function AccountOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    apiRequest<Order[]>(`/orders${buildQuery({ page, per_page: 10 })}`)
      .then(({ data, meta: m }) => {
        setOrders(data);
        setMeta(m ?? null);
      })
      .finally(() => setLoading(false));
  }, [page]);

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  return (
    <div>
      <h2 className="font-display text-2xl font-bold">Orders</h2>
      <p className="mt-1 text-sm text-muted-foreground">Track fulfillment and payment status.</p>

      {orders.length === 0 ? (
        <div className="mt-8 rounded-2xl border border-dashed border-border bg-white p-8 text-center">
          <p className="text-muted-foreground">You have not placed any orders yet.</p>
          <Link href="/products" className="mt-4 inline-block">
            <Button>Browse products</Button>
          </Link>
        </div>
      ) : (
        <ul className="mt-6 divide-y divide-border overflow-hidden rounded-2xl border border-border bg-white">
          {orders.map((order) => (
            <li key={order.id} className="px-5 py-4">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p className="font-medium">{order.order_number}</p>
                  <p className="text-sm text-muted-foreground">
                    {formatDate(order.created_at)}
                    {order.item_count ? ` · ${order.item_count} item${order.item_count > 1 ? "s" : ""}` : ""}
                  </p>
                  {order.items?.length ? (
                    <p className="mt-2 line-clamp-2 text-sm text-black/60">
                      {order.items.map((i) => `${i.product_name} × ${i.quantity}`).join(", ")}
                    </p>
                  ) : null}
                </div>
                <div className="flex flex-col items-start gap-2 sm:items-end">
                  <div className="flex flex-wrap gap-2">
                    <OrderStatusBadge status={order.status} />
                    <OrderStatusBadge status={order.payment_status} />
                  </div>
                  <p className="font-semibold">{formatCurrency(order.total_amount)}</p>
                  <Link href={`/account/orders/${order.id}`}>
                    <Button variant="secondary" size="sm">
                      View status
                    </Button>
                  </Link>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}

      {meta && meta.last_page > 1 ? (
        <div className="mt-6 flex justify-center gap-3">
          <Button variant="secondary" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Previous
          </Button>
          <span className="self-center text-sm text-muted-foreground">
            Page {meta.current_page} of {meta.last_page}
          </span>
          <Button
            variant="secondary"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Next
          </Button>
        </div>
      ) : null}
    </div>
  );
}
