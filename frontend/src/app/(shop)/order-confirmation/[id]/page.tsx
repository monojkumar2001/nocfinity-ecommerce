"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { AlertCircle, CheckCircle2, Clock3 } from "lucide-react";
import { apiRequest } from "@/lib/api";
import { getToken } from "@/lib/auth";
import type { Order } from "@/lib/types";
import { formatCurrency } from "@/lib/format";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

export default function OrderConfirmationPage() {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!getToken()) {
      setLoading(false);
      return;
    }
    apiRequest<Order>(`/orders/${id}`)
      .then(({ data }) => setOrder(data))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  const paid = order?.payment_status === "paid";
  const failed = order?.payment_status === "failed";

  return (
    <div className="mx-auto max-w-lg px-4 py-16 text-center sm:px-6">
      {paid ? (
        <CheckCircle2 className="mx-auto h-14 w-14 text-green-600" />
      ) : failed ? (
        <AlertCircle className="mx-auto h-14 w-14 text-sale" />
      ) : (
        <Clock3 className="mx-auto h-14 w-14 text-black/50" />
      )}

      <h1 className="font-display mt-4 text-3xl font-bold">
        {paid ? "Thank you!" : failed ? "Payment failed" : "Order pending"}
      </h1>

      <p className="mt-2 text-muted-foreground">
        {order ? (
          paid ? (
            <>
              Order <strong>{order.order_number}</strong> is confirmed for{" "}
              {formatCurrency(order.total_amount)}. Status:{" "}
              <strong className="capitalize">{order.status}</strong>.
            </>
          ) : failed ? (
            <>
              Order <strong>{order.order_number}</strong> was not marked as paid. You can retry
              checkout from your cart.
            </>
          ) : (
            <>
              Order <strong>{order.order_number}</strong> exists with payment status{" "}
              <strong>{order.payment_status}</strong>. It is not confirmed as paid yet.
            </>
          )
        ) : (
          "We could not load this order. Sign in and open My Orders."
        )}
      </p>

      <div className="mt-8 flex flex-wrap justify-center gap-3">
        <Link href={order ? `/account/orders/${order.id}` : "/account/orders"}>
          <Button variant="secondary">View order</Button>
        </Link>
        <Link href={paid ? "/products" : "/cart"}>
          <Button>{paid ? "Continue shopping" : "Back to cart"}</Button>
        </Link>
      </div>
    </div>
  );
}
