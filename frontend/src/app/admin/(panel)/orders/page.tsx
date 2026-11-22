"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Order, PaginationMeta } from "@/lib/types";
import { formatCurrency, formatDate } from "@/lib/format";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { Pagination } from "@/components/admin/Pagination";

function customerLabel(order: Order): string {
  if (typeof order.customer === "string") return order.customer;
  return order.customer?.name ?? order.customer?.email ?? "—";
}

export default function AdminOrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);

  const load = useCallback(() => {
    setLoading(true);
    apiRequest<Order[]>(
      `/admin/orders${buildQuery({
        per_page: 15,
        page,
        search: search.trim() || undefined,
        status: status || undefined,
      })}`,
    )
      .then(({ data, meta: m }) => {
        setOrders(data);
        setMeta(m ?? null);
      })
      .finally(() => setLoading(false));
  }, [page, search, status]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Orders</h1>

      <form
        className="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-white p-4"
        onSubmit={(e) => {
          e.preventDefault();
          setPage(1);
          load();
        }}
      >
        <Input
          label="Search"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Order # or customer"
          className="min-w-[180px] flex-1"
        />
        <Select
          label="Status"
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All statuses</option>
          {["pending", "paid", "processing", "shipped", "delivered", "cancelled"].map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </Select>
        <Button type="submit" variant="secondary">
          Filter
        </Button>
      </form>

      {loading ? (
        <Spinner className="mt-12" />
      ) : (
        <>
          <div className="mt-6 overflow-x-auto rounded-xl border border-border bg-white">
            <table className="w-full text-sm">
              <thead className="border-b border-border bg-surface text-left text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">Order</th>
                  <th className="px-4 py-3">Customer</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Payment</th>
                  <th className="px-4 py-3">Total</th>
                  <th className="px-4 py-3">Date</th>
                </tr>
              </thead>
              <tbody>
                {orders.map((o) => (
                  <tr key={o.id} className="border-b border-border">
                    <td className="px-4 py-3">
                      <Link href={`/admin/orders/${o.id}`} className="font-medium text-accent hover:underline">
                        {o.order_number}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{customerLabel(o)}</td>
                    <td className="px-4 py-3">{o.status}</td>
                    <td className="px-4 py-3">{o.payment_status}</td>
                    <td className="px-4 py-3">{formatCurrency(o.total_amount)}</td>
                    <td className="px-4 py-3 text-muted-foreground">{formatDate(o.created_at)}</td>
                  </tr>
                ))}
                {!orders.length ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                      No orders found.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
