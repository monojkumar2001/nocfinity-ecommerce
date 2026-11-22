"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";
import { apiRequest } from "@/lib/api";
import type { DashboardData } from "@/lib/types";
import { formatCurrency, formatDate } from "@/lib/format";
import { Spinner } from "@/components/ui/Spinner";

export default function AdminDashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    apiRequest<DashboardData>("/admin/dashboard")
      .then(({ data: d }) => setData(d))
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load dashboard"));
  }, []);

  if (error) {
    return <p className="text-red-600">{error}</p>;
  }

  if (!data) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  const stats = [
    { label: "Today's orders", value: String(data.stats.todays_orders) },
    { label: "Today's revenue", value: formatCurrency(data.stats.todays_revenue) },
    { label: "Customers", value: String(data.stats.total_customers) },
    { label: "Low stock", value: String(data.stats.low_stock_count) },
  ];

  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Dashboard</h1>
      <p className="text-sm text-muted-foreground">Store overview — last 30 days</p>

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {stats.map((s) => (
          <div key={s.label} className="rounded-xl border border-border bg-white p-5">
            <p className="text-sm text-muted-foreground">{s.label}</p>
            <p className="mt-1 text-2xl font-semibold">{s.value}</p>
          </div>
        ))}
      </div>

      <div className="mt-8 rounded-xl border border-border bg-white p-6">
        <h2 className="font-semibold">Sales (30 days)</h2>
        <div className="mt-4 h-72 w-full">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={data.sales_chart}>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
              <XAxis dataKey="day" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip formatter={(v) => formatCurrency(Number(v))} />
              <Line type="monotone" dataKey="revenue" stroke="#0b4f6c" strokeWidth={2} dot={false} />
            </LineChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="mt-8 rounded-xl border border-border bg-white">
        <div className="border-b border-border px-4 py-3">
          <h2 className="font-semibold">Recent orders</h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="border-b border-border bg-surface/80 text-left text-muted-foreground">
              <tr>
                <th className="px-4 py-3 font-medium">Order</th>
                <th className="px-4 py-3 font-medium">Customer</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium">Total</th>
                <th className="px-4 py-3 font-medium">Date</th>
              </tr>
            </thead>
            <tbody>
              {data.recent_orders.map((order) => (
                <tr key={order.id} className="border-b border-border last:border-0">
                  <td className="px-4 py-3">
                    <Link href={`/admin/orders/${order.id}`} className="font-medium text-accent hover:underline">
                      {order.order_number}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {typeof order.customer === "string"
                      ? order.customer
                      : order.customer?.name ?? "—"}
                  </td>
                  <td className="px-4 py-3">{order.status}</td>
                  <td className="px-4 py-3">{formatCurrency(order.total_amount)}</td>
                  <td className="px-4 py-3 text-muted-foreground">{formatDate(order.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}