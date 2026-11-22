import Link from "next/link";
import { ArrowRight, Tag } from "lucide-react";
import { formatCurrency } from "@/lib/format";
import type { Cart } from "@/lib/types";

export function CartSummary({
  cart,
  checkoutHref = "/checkout",
  couponSlot,
  showCheckoutButton = true,
}: {
  cart: Cart;
  checkoutHref?: string;
  couponSlot?: React.ReactNode;
  showCheckoutButton?: boolean;
}) {
  const delivery = cart.shipping ?? 0;
  const discountPct =
    cart.subtotal > 0 && cart.discount > 0
      ? Math.round((cart.discount / cart.subtotal) * 100)
      : 0;
  const total = cart.total;

  return (
    <div className="rounded-[20px] border border-black/10 bg-white p-5 sm:p-6">
      <h2 className="text-xl font-normal sm:text-2xl">Order Summary</h2>
      <dl className="mt-6 space-y-5 text-base font-normal">
        <div className="flex justify-between">
          <dt className="text-black/60">Subtotal</dt>
          <dd className="font-normal text-black">{formatCurrency(cart.subtotal)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-black/60">
            Discount{discountPct ? ` (-${discountPct}%)` : ""}
          </dt>
          <dd className="text-sale">-{formatCurrency(cart.discount)}</dd>
        </div>
        <div className="flex justify-between">
          <dt className="text-black/60">Delivery Fee</dt>
          <dd className="text-black">{delivery > 0 ? formatCurrency(delivery) : "Free"}</dd>
        </div>
        <div className="flex justify-between border-t border-black/10 pt-5 text-xl">
          <dt>Total</dt>
          <dd>{formatCurrency(cart.items.length ? total : 0)}</dd>
        </div>
      </dl>

      {couponSlot ? (
        <div className="mt-6">{couponSlot}</div>
      ) : cart.coupon ? (
        <p className="mt-4 flex items-center gap-2 text-sm text-black/60">
          <Tag className="h-4 w-4" /> {cart.coupon.code}
        </p>
      ) : null}

      {showCheckoutButton ? (
        <Link href={checkoutHref} className="mt-6 block">
          <button
            type="button"
            disabled={!cart.items.length}
            className="flex h-[60px] w-full items-center justify-center gap-3 rounded-full bg-black text-base font-normal text-white transition hover:bg-black/80 disabled:opacity-50"
          >
            Go to Checkout <ArrowRight className="h-5 w-5" />
          </button>
        </Link>
      ) : null}
    </div>
  );
}
