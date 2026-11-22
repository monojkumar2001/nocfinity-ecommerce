"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { apiRequest } from "@/lib/api";
import { formatCurrency, formatDate } from "@/lib/format";
import type { Order } from "@/lib/types";
import { Spinner } from "@/components/ui/Spinner";

type CustomerDetail = {
  id: number;
  name: string;
  email: string;
  created_at?: string;
  orders?: Order[];
  stats?: { orders_count: number; total_spent: number };
};

export default function AdminCustomerDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [customer, setCustomer] = useState<CustomerDetail | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    apiRequest<CustomerDetail>(`/admin/customers/${id}`)
      .then(({ data }) => setCustomer(data))
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load"));
  }, [id]);

  if (error) return <p className="text-red-600">{error}</p>;
  if (!customer) return <Spinner className="mt-12" />;

  const stats = customer.stats ?? {
    orders_count: customer.orders?.length ?? 0,
    total_spent: (customer.orders ?? [])
      .filter((o) => o.payment_status === "paid")
      .reduce((sum, o) => sum + o.total_amount, 0),
  };

  return (
    <div className="max-w-2xl">
      <Link href="/admin/customers" className="text-sm text-accent hover:underline">
        ← Customers
      </Link>
      <h1 className="font-display mt-2 text-2xl font-bold">{customer.name}</h1>
      <p className="text-muted-foreground">{customer.email}</p>
      <p className="mt-2 text-sm">
        {stats.orders_count} orders · {formatCurrency(stats.total_spent)} lifetime · joined{" "}
        {formatDate(customer.created_at)}
      </p>

      <h2 className="mt-8 font-semibold">Order history</h2>
      {customer.orders?.length ? (
        <ul className="mt-3 divide-y divide-border rounded-xl border border-border bg-white text-sm">
          {customer.orders.map((o) => (
            <li key={o.id} className="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
              <div>
                <Link href={`/admin/orders/${o.id}`} className="font-medium text-accent hover:underline">
                  {o.order_number}
                </Link>
                <p className="text-xs text-muted-foreground">
                  {o.status} · {o.payment_status}
                </p>
              </div>
              <span className="text-muted-foreground">
                {formatDate(o.created_at)} · {formatCurrency(o.total_amount)}
              </span>
            </li>
          ))}
        </ul>
      ) : (
        <p className="mt-3 text-sm text-muted-foreground">No orders yet.</p>
      )}
    </div>
  );
}
