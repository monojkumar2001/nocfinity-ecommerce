"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { apiRequest } from "@/lib/api";
import type { Order } from "@/lib/types";
import { formatCurrency, formatDate } from "@/lib/format";
import { OrderStatusBadge } from "@/components/account/OrderStatusBadge";
import { OrderTimeline } from "@/components/account/OrderTimeline";
import { Spinner } from "@/components/ui/Spinner";

export default function AccountOrderDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiRequest<Order>(`/orders/${id}`)
      .then(({ data }) => setOrder(data))
      .catch(() => setOrder(null))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  if (!order) {
    return <p className="py-12 text-center text-muted-foreground">Order not found.</p>;
  }

  const addr = order.shipping_address;

  return (
    <div>
      <Link href="/account/orders" className="text-sm text-accent hover:underline">
        ← Back to orders
      </Link>

      <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 className="font-display text-2xl font-bold">{order.order_number}</h2>
          <p className="mt-1 text-sm text-muted-foreground">Placed {formatDate(order.created_at)}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <OrderStatusBadge status={order.status} />
          <OrderStatusBadge status={order.payment_status} />
        </div>
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-5">
        <section className="rounded-2xl border border-border bg-white p-6 lg:col-span-3">
          <h3 className="font-semibold">Items</h3>
          <ul className="mt-4 divide-y divide-border">
            {(order.items ?? []).map((item, idx) => (
              <li key={`${item.product_sku}-${idx}`} className="flex justify-between gap-4 py-3 text-sm">
                <span>
                  {item.product_name}
                  <span className="block text-xs text-muted-foreground">
                    {item.product_sku} · Qty {item.quantity}
                  </span>
                </span>
                <span className="shrink-0 font-medium">{formatCurrency(item.total)}</span>
              </li>
            ))}
          </ul>
          <dl className="mt-4 space-y-1 border-t border-border pt-4 text-sm">
            <div className="flex justify-between">
              <dt>Subtotal</dt>
              <dd>{formatCurrency(order.subtotal ?? 0)}</dd>
            </div>
            <div className="flex justify-between">
              <dt>Discount</dt>
              <dd>-{formatCurrency(order.discount_amount ?? 0)}</dd>
            </div>
            <div className="flex justify-between">
              <dt>Shipping</dt>
              <dd>{formatCurrency(order.shipping_amount ?? 0)}</dd>
            </div>
            <div className="flex justify-between font-semibold">
              <dt>Total</dt>
              <dd>{formatCurrency(order.total_amount)}</dd>
            </div>
          </dl>
          {order.customer_note ? (
            <p className="mt-4 rounded-xl bg-surface px-3 py-2 text-sm text-black/70">
              Note: {order.customer_note}
            </p>
          ) : null}
        </section>

        <div className="space-y-6 lg:col-span-2">
          <section className="rounded-2xl border border-border bg-white p-6">
            <h3 className="font-semibold">Order status</h3>
            <div className="mt-4">
              {order.status_timeline?.length ? (
                <OrderTimeline steps={order.status_timeline} />
              ) : (
                <OrderStatusBadge status={order.status} />
              )}
            </div>
          </section>

          {addr ? (
            <section className="rounded-2xl border border-border bg-white p-6 text-sm">
              <h3 className="font-semibold">Shipping address</h3>
              <p className="mt-3 leading-relaxed text-muted-foreground">
                {addr.name}
                <br />
                {addr.line1}
                {addr.line2 ? (
                  <>
                    <br />
                    {addr.line2}
                  </>
                ) : null}
                <br />
                {addr.city}, {addr.state} {addr.postal_code}
                <br />
                {addr.country}
                <br />
                {addr.phone}
              </p>
            </section>
          ) : null}
        </div>
      </div>
    </div>
  );
}
