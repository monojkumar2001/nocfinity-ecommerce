"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { apiRequest, buildQuery } from "@/lib/api";
import { getUser, logout } from "@/lib/auth";
import { formatCurrency, formatDate } from "@/lib/format";
import type { Order, User } from "@/lib/types";
import { useCart } from "@/hooks/useCart";
import { OrderStatusBadge } from "@/components/account/OrderStatusBadge";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

export default function AccountDashboardPage() {
  const [user, setUser] = useState<User | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [orderTotal, setOrderTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const { cart, refreshCart } = useCart();

  useEffect(() => {
    setUser(getUser());
    Promise.all([
      apiRequest<Order[]>(`/orders${buildQuery({ page: 1, per_page: 5 })}`).then(({ data, meta }) => {
        setOrders(data);
        setOrderTotal(meta?.total ?? data.length);
      }),
      refreshCart().catch(() => undefined),
    ]).finally(() => setLoading(false));
  }, [refreshCart]);

  const cartCount = cart?.items.reduce((n, i) => n + i.quantity, 0) ?? 0;

  async function handleLogout() {
    await logout().catch(() => undefined);
    window.location.href = "/";
  }

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <section className="rounded-2xl border border-border bg-white p-6">
        <p className="text-sm text-muted-foreground">Signed in as</p>
        <h2 className="font-display mt-1 text-2xl font-bold">{user?.name ?? "Customer"}</h2>
        <p className="text-sm text-muted-foreground">{user?.email}</p>
        <div className="mt-4 flex flex-wrap gap-3">
          <Link href="/account/profile">
            <Button variant="secondary" size="sm">
              Edit profile
            </Button>
          </Link>
          <Button variant="secondary" size="sm" onClick={handleLogout}>
            Log out
          </Button>
        </div>
      </section>

      <section className="grid gap-4 sm:grid-cols-3">
        <Link href="/account/orders" className="rounded-2xl border border-border bg-white p-5 hover:bg-surface">
          <p className="text-sm text-muted-foreground">Orders</p>
          <p className="mt-2 text-2xl font-semibold">{orderTotal}</p>
          <p className="mt-1 text-xs text-black/50">View status & history</p>
        </Link>
        <Link href="/cart" className="rounded-2xl border border-border bg-white p-5 hover:bg-surface">
          <p className="text-sm text-muted-foreground">Cart</p>
          <p className="mt-2 text-2xl font-semibold">{cartCount}</p>
          <p className="mt-1 text-xs text-black/50">
            {cart ? formatCurrency(cart.total) : "Empty"}
          </p>
        </Link>
        <Link href="/account/password" className="rounded-2xl border border-border bg-white p-5 hover:bg-surface">
          <p className="text-sm text-muted-foreground">Security</p>
          <p className="mt-2 text-2xl font-semibold">Password</p>
          <p className="mt-1 text-xs text-black/50">Change your password</p>
        </Link>
      </section>

      <section>
        <div className="mb-4 flex items-center justify-between">
          <h3 className="font-display text-xl font-bold">Recent orders</h3>
          <Link href="/account/orders" className="text-sm text-accent hover:underline">
            View all
          </Link>
        </div>
        {orders.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-border bg-white p-8 text-center">
            <p className="text-muted-foreground">No orders yet.</p>
            <Link href="/products" className="mt-4 inline-block">
              <Button>Start shopping</Button>
            </Link>
          </div>
        ) : (
          <ul className="divide-y divide-border overflow-hidden rounded-2xl border border-border bg-white">
            {orders.map((order) => (
              <li key={order.id}>
                <Link
                  href={`/account/orders/${order.id}`}
                  className="flex flex-col gap-3 px-5 py-4 transition hover:bg-surface sm:flex-row sm:items-center sm:justify-between"
                >
                  <div>
                    <p className="font-medium">{order.order_number}</p>
                    <p className="text-sm text-muted-foreground">{formatDate(order.created_at)}</p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <OrderStatusBadge status={order.status} />
                    <OrderStatusBadge status={order.payment_status} />
                    <span className="font-semibold">{formatCurrency(order.total_amount)}</span>
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
