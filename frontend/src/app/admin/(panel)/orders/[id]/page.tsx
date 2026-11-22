"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { apiRequest } from "@/lib/api";
import type { Order } from "@/lib/types";
import { formatCurrency, formatDate } from "@/lib/format";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { Can } from "@/components/admin/Can";

type AdminOrder = Order & {
  allowed_transitions?: string[];
};

const LIFECYCLE = ["pending", "paid", "processing", "shipped", "delivered", "cancelled"];

export default function AdminOrderDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [status, setStatus] = useState("");
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  function load() {
    apiRequest<AdminOrder>(`/admin/orders/${id}`)
      .then(({ data }) => {
        setOrder(data);
        setStatus(data.allowed_transitions?.[0] ?? data.status);
      })
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    load();
  }, [id]);

  const transitions = useMemo(() => order?.allowed_transitions ?? [], [order]);

  async function updateStatus(e: React.FormEvent) {
    e.preventDefault();
    setMessage(null);
    setError(null);
    try {
      await apiRequest(`/admin/orders/${id}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status }),
      });
      setMessage("Status updated — customer emailed.");
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Update failed");
    }
  }

  if (loading) return <Spinner className="mt-12" />;
  if (!order) return <p>Order not found</p>;

  const customer =
    typeof order.customer === "object" && order.customer
      ? order.customer
      : { name: typeof order.customer === "string" ? order.customer : null, email: null };
  const address = order.shipping_address;

  return (
    <div className="max-w-3xl">
      <Link href="/admin/orders" className="text-sm text-accent hover:underline">
        ← Orders
      </Link>
      <h1 className="font-display mt-2 text-2xl font-bold">{order.order_number}</h1>
      <p className="text-sm text-muted-foreground">
        {formatDate(order.created_at)} · Payment: {order.payment_status}
      </p>

      <ol className="mt-4 flex flex-wrap gap-2 text-xs">
        {LIFECYCLE.filter((s) => s !== "cancelled").map((step) => (
          <li
            key={step}
            className={`rounded-full px-2.5 py-1 ${
              order.status === step ? "bg-accent-soft text-accent" : "bg-surface text-muted-foreground"
            }`}
          >
            {step}
          </li>
        ))}
        {order.status === "cancelled" ? (
          <li className="rounded-full bg-red-50 px-2.5 py-1 text-red-700">cancelled</li>
        ) : null}
      </ol>

      <div className="mt-6 grid gap-4 sm:grid-cols-2">
        <div className="rounded-xl border border-border bg-white p-4 text-sm">
          <h2 className="font-semibold">Customer</h2>
          <p className="mt-2 font-medium">{customer.name ?? "—"}</p>
          <p className="text-muted-foreground">{customer.email ?? "—"}</p>
          {customer.id ? (
            <Link href={`/admin/customers/${customer.id}`} className="mt-2 inline-block text-accent hover:underline">
              View customer
            </Link>
          ) : null}
        </div>
        <div className="rounded-xl border border-border bg-white p-4 text-sm">
          <h2 className="font-semibold">Shipping</h2>
          {address ? (
            <div className="mt-2 space-y-0.5 text-muted-foreground">
              <p>{address.name} · {address.phone}</p>
              <p>{address.line1}</p>
              {address.line2 ? <p>{address.line2}</p> : null}
              <p>
                {address.city}, {address.state} {address.postal_code}
              </p>
              <p>{address.country}</p>
            </div>
          ) : (
            <p className="mt-2 text-muted-foreground">No address on file.</p>
          )}
          {order.customer_note ? (
            <p className="mt-3 text-foreground">Note: {order.customer_note}</p>
          ) : null}
        </div>
      </div>

      <Can
        permission="orders.update_status"
        fallback={
          <p className="mt-6 rounded-xl border border-border bg-white p-4 text-sm text-muted-foreground">
            Status: <span className="font-medium text-foreground">{order.status}</span>
          </p>
        }
      >
        {transitions.length === 0 ? (
          <p className="mt-6 rounded-xl border border-border bg-white p-4 text-sm text-muted-foreground">
            No further status changes are allowed for a “{order.status}” order.
          </p>
        ) : (
          <form onSubmit={updateStatus} className="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-white p-4">
            <Select label="Next status" value={status} onChange={(e) => setStatus(e.target.value)}>
              {transitions.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </Select>
            <Button type="submit">Update status</Button>
            {message ? <span className="text-sm text-green-700">{message}</span> : null}
            {error ? <span className="text-sm text-red-600">{error}</span> : null}
          </form>
        )}
      </Can>

      <div className="mt-6 rounded-xl border border-border bg-white p-6">
        <h2 className="font-semibold">Line items</h2>
        <ul className="mt-3 divide-y divide-border text-sm">
          {(order.items ?? []).map((item, i) => (
            <li key={i} className="flex justify-between py-2">
              <span>
                {item.product_name} × {item.quantity}
                <span className="ml-2 text-xs text-muted-foreground">{item.product_sku}</span>
              </span>
              <span>{formatCurrency(item.total)}</span>
            </li>
          ))}
        </ul>
        <div className="mt-4 space-y-1 text-right text-sm">
          {order.subtotal != null ? <p>Subtotal: {formatCurrency(order.subtotal)}</p> : null}
          {order.discount_amount ? <p>Discount: −{formatCurrency(order.discount_amount)}</p> : null}
          <p className="font-semibold">Total: {formatCurrency(order.total_amount)}</p>
        </div>
      </div>
    </div>
  );
}
