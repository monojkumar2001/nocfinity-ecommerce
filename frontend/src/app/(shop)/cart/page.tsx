"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Minus, Plus, Tag, Trash2 } from "lucide-react";
import { useCart } from "@/hooks/useCart";
import { formatCurrency } from "@/lib/format";
import { resolveMediaUrl } from "@/lib/media";
import type { CartItem } from "@/lib/types";
import { CartSummary } from "@/components/shop/CartSummary";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

function CartLineItem({
  item,
  updateQuantity,
  removeItem,
}: {
  item: CartItem;
  updateQuantity: (id: number, qty: number) => Promise<void>;
  removeItem: (id: number) => Promise<void>;
}) {
  return (
    <div className="flex gap-4 border-b border-black/10 py-5 last:border-0 sm:gap-5">
      <Link
        href={`/products/${item.slug}`}
        className="relative h-[99px] w-[99px] shrink-0 overflow-hidden rounded-[8.66px] bg-card-bg sm:h-[124px] sm:w-[124px]"
      >
        {resolveMediaUrl(item.image) ? (
          <Image
            src={resolveMediaUrl(item.image)!}
            alt={item.name}
            fill
            unoptimized
            className="object-cover"
            sizes="124px"
          />
        ) : null}
      </Link>

      <div className="flex min-w-0 flex-1 flex-col justify-between">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <Link
              href={`/products/${item.slug}`}
              className="block truncate text-base font-normal sm:text-xl"
            >
              {item.name}
            </Link>
            {item.size ? (
              <p className="mt-1 text-xs font-normal text-black sm:text-sm">
                <span className="text-black/60">Size:</span> {item.size}
              </p>
            ) : null}
            {item.color ? (
              <p className="text-xs font-normal text-black sm:text-sm">
                <span className="text-black/60">Color:</span> {item.color}
              </p>
            ) : null}
          </div>
          <button
            type="button"
            onClick={() => removeItem(item.id)}
            className="shrink-0 text-sale hover:opacity-80"
            aria-label="Remove"
          >
            <Trash2 className="h-5 w-5 sm:h-6 sm:w-6" />
          </button>
        </div>

        <div className="mt-3 flex items-end justify-between gap-3">
          <p className="text-xl font-normal sm:text-2xl">{formatCurrency(item.price)}</p>
          <div className="flex items-center gap-5 rounded-full bg-surface px-4 py-2 sm:px-5 sm:py-3">
            <button
              type="button"
              onClick={() => updateQuantity(item.id, Math.max(1, item.quantity - 1))}
              aria-label="Decrease"
            >
              <Minus className="h-4 w-4 sm:h-5 sm:w-5" />
            </button>
            <span className="min-w-4 text-center text-sm font-normal">{item.quantity}</span>
            <button
              type="button"
              onClick={() => updateQuantity(item.id, Math.min(item.stock, item.quantity + 1))}
              aria-label="Increase"
            >
              <Plus className="h-4 w-4 sm:h-5 sm:w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function CartPage() {
  const { cart, loading, refreshCart, updateQuantity, removeItem, applyCoupon, removeCoupon } =
    useCart();
  const [coupon, setCoupon] = useState("");
  const [couponError, setCouponError] = useState<string | null>(null);

  useEffect(() => {
    refreshCart().catch(() => undefined);
  }, [refreshCart]);

  if (loading && !cart) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  if (!cart?.items.length) {
    return (
      <div className="container-shop py-20 text-center">
        <h1 className="font-display text-3xl font-bold uppercase">Your cart is empty</h1>
        <p className="mt-2 font-normal text-black/60">Discover our latest products.</p>
        <Link href="/products" className="mt-6 inline-block">
          <Button size="lg">Continue shopping</Button>
        </Link>
      </div>
    );
  }

  async function handleCoupon(e: React.FormEvent) {
    e.preventDefault();
    setCouponError(null);
    try {
      await applyCoupon(coupon.trim());
      setCoupon("");
    } catch (err) {
      setCouponError(err instanceof Error ? err.message : "Invalid coupon");
    }
  }

  return (
    <div className="container-shop py-6 sm:py-8">
      <p className="text-sm font-normal text-black/60">
        <Link href="/" className="hover:text-black">
          Home
        </Link>{" "}
        &gt; <span className="text-black">Cart</span>
      </p>
      <h1 className="font-display mt-3 text-[32px] font-bold uppercase leading-none sm:text-[40px]">
        Your cart
      </h1>

      <div className="mt-5 grid gap-5 lg:grid-cols-[1fr_505px]">
        <div className="rounded-[20px] border border-black/10 px-4 sm:px-6">
          {cart.items.map((item) => (
            <CartLineItem
              key={item.id}
              item={item}
              updateQuantity={updateQuantity}
              removeItem={removeItem}
            />
          ))}
        </div>

        <CartSummary
          cart={cart}
          couponSlot={
            <div className="space-y-2">
              <form onSubmit={handleCoupon} className="flex gap-3">
                <div className="relative flex-1">
                  <Tag className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-black/40" />
                  <input
                    value={coupon}
                    onChange={(e) => setCoupon(e.target.value)}
                    placeholder="Add promo code"
                    className="h-12 w-full rounded-full bg-surface py-3 pl-11 pr-4 text-sm font-normal outline-none placeholder:text-black/40"
                  />
                </div>
                <button
                  type="submit"
                  className="h-12 shrink-0 rounded-full bg-black px-6 text-sm font-normal text-white hover:bg-black/80"
                >
                  Apply
                </button>
              </form>
              {couponError ? <p className="text-sm text-sale">{couponError}</p> : null}
              {cart.coupon ? (
                <button
                  type="button"
                  className="text-sm font-normal text-black/60 underline"
                  onClick={() => removeCoupon().catch(() => undefined)}
                >
                  Remove {cart.coupon.code}
                </button>
              ) : null}
            </div>
          }
        />
      </div>
    </div>
  );
}
