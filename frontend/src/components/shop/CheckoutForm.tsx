"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { loadStripe } from "@stripe/stripe-js";
import { Elements, PaymentElement, useElements, useStripe } from "@stripe/react-stripe-js";
import { Tag } from "lucide-react";
import { apiRequest } from "@/lib/api";
import { getToken } from "@/lib/auth";
import type { Order, ShippingAddress } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { CartSummary } from "@/components/shop/CartSummary";
import { useCart } from "@/hooks/useCart";
import { validateShippingAddress } from "@/lib/validation";

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY ?? "");

type CheckoutInit = {
  order: Order;
  client_secret: string;
  payment_intent_id: string;
};

function PaymentStep({
  clientSecret,
  orderId,
  paymentIntentId,
}: {
  clientSecret: string;
  orderId: number;
  paymentIntentId: string;
}) {
  const stripe = useStripe();
  const elements = useElements();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handlePay(e: React.FormEvent) {
    e.preventDefault();
    if (!stripe || !elements) return;
    setSubmitting(true);
    setError(null);

    let intentId = paymentIntentId;
    let status: string | undefined;

    // Manual-capture intents become requires_capture after confirm — not succeeded.
    // If already authorized (retry / double-submit), skip confirmPayment.
    const existing = await stripe.retrievePaymentIntent(clientSecret);
    const existingStatus = existing.paymentIntent?.status;
    if (existingStatus === "requires_capture" || existingStatus === "succeeded") {
      intentId = existing.paymentIntent?.id ?? paymentIntentId;
      status = existingStatus;
    } else {
      const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
        elements,
        redirect: "if_required",
      });
      if (stripeError) {
        // Do not call /checkout/confirm — order stays unpaid.
        setError(stripeError.message ?? "Payment failed");
        setSubmitting(false);
        return;
      }
      intentId = paymentIntent?.id ?? paymentIntentId;
      status = paymentIntent?.status;
    }

    if (status !== "requires_capture" && status !== "succeeded") {
      setError(`Payment status: ${status}. Order was not marked as paid.`);
      setSubmitting(false);
      return;
    }

    try {
      await apiRequest<{ order: Order }>("/checkout/confirm", {
        method: "POST",
        body: JSON.stringify({ order_id: orderId, payment_intent_id: intentId }),
      });
      router.push(`/order-confirmation/${orderId}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not confirm order");
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handlePay} className="space-y-4 rounded-xl border border-border bg-white p-6">
      <h2 className="font-display text-lg font-semibold">Payment</h2>
      <p className="text-sm text-muted-foreground">
        Stripe Test Mode — use card <code>4242 4242 4242 4242</code>. Card is authorized first; charge
        happens after stock is confirmed. Failed payments are never marked paid.
      </p>
      <PaymentElement />
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
      <Button type="submit" disabled={!stripe || submitting} className="w-full">
        {submitting ? "Confirming payment…" : "Pay now"}
      </Button>
      <p className="text-xs text-muted-foreground">
        Stock is re-checked before your card is charged. If inventory changed, payment is cancelled and nothing is charged.
      </p>
    </form>
  );
}

const emptyAddress: ShippingAddress = {
  name: "",
  phone: "",
  line1: "",
  line2: "",
  city: "",
  state: "",
  postal_code: "",
  country: "United States",
};

export function CheckoutForm() {
  const router = useRouter();
  const { cart, loading: cartLoading, refreshCart, applyCoupon, removeCoupon } = useCart();
  const [ready, setReady] = useState(false);
  const [authed, setAuthed] = useState(false);
  const [cartReady, setCartReady] = useState(false);
  const [address, setAddress] = useState<ShippingAddress>(emptyAddress);
  const [note, setNote] = useState("");
  const [coupon, setCoupon] = useState("");
  const [couponError, setCouponError] = useState<string | null>(null);
  const [step, setStep] = useState<"shipping" | "payment">("shipping");
  const [checkout, setCheckout] = useState<CheckoutInit | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // Avoid SSR/client mismatch: token + cart live only in the browser.
  useEffect(() => {
    const hasToken = Boolean(getToken());
    setAuthed(hasToken);
    setReady(true);

    if (!hasToken) {
      setCartReady(true);
      return;
    }

    refreshCart()
      .catch(() => undefined)
      .finally(() => setCartReady(true));
  }, [refreshCart]);

  if (!ready || !cartReady || cartLoading) {
    return (
      <div className="rounded-xl border border-border bg-white p-8 text-center text-muted-foreground">
        Loading checkout…
      </div>
    );
  }

  if (!authed) {
    return (
      <div className="rounded-xl border border-border bg-white p-8 text-center">
        <p className="text-muted-foreground">Sign in to complete checkout.</p>
        <Button className="mt-4" onClick={() => router.push("/login?next=/checkout")}>
          Sign in
        </Button>
      </div>
    );
  }

  if (!cart?.items.length) {
    return (
      <p className="text-center text-muted-foreground">
        Your cart is empty.{" "}
        <button type="button" className="text-accent underline" onClick={() => router.push("/products")}>
          Continue shopping
        </button>
      </p>
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

  async function startCheckout(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    const addressError = validateShippingAddress(address);
    if (addressError) {
      setError(addressError);
      return;
    }
    setLoading(true);
    try {
      const { data } = await apiRequest<CheckoutInit>("/checkout", {
        method: "POST",
        body: JSON.stringify({ shipping_address: address, customer_note: note || null }),
      });
      setCheckout(data);
      setStep("payment");
      await refreshCart();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Checkout failed");
    } finally {
      setLoading(false);
    }
  }

  function field<K extends keyof ShippingAddress>(key: K, value: string) {
    setAddress((a) => ({ ...a, [key]: value }));
  }

  return (
    <div className="grid gap-8 lg:grid-cols-3">
      <div className="space-y-6 lg:col-span-2">
        {step === "shipping" ? (
          <form onSubmit={startCheckout} className="space-y-4 rounded-xl border border-border bg-white p-6">
            <h2 className="font-display text-lg font-semibold">Shipping address</h2>
            <AddressFields address={address} field={field} />
            <label className="flex flex-col gap-1.5 text-sm">
              <span className="font-medium">Order note (optional)</span>
              <textarea
                value={note}
                onChange={(e) => setNote(e.target.value)}
                rows={3}
                className="rounded-lg border border-border px-3 py-2"
              />
            </label>
            {error ? <p className="text-sm text-red-600">{error}</p> : null}
            <Button type="submit" disabled={loading} className="w-full sm:w-auto">
              {loading ? "Preparing…" : "Continue to payment"}
            </Button>
          </form>
        ) : checkout ? (
          <Elements stripe={stripePromise} options={{ clientSecret: checkout.client_secret }}>
            <PaymentStep
              clientSecret={checkout.client_secret}
              orderId={checkout.order.id}
              paymentIntentId={checkout.payment_intent_id}
            />
          </Elements>
        ) : null}
      </div>
      <CartSummary
        cart={cart}
        showCheckoutButton={false}
        couponSlot={
          step === "shipping" ? (
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
          ) : undefined
        }
      />
    </div>
  );
}

function AddressFields({
  address,
  field,
}: {
  address: ShippingAddress;
  field: (key: keyof ShippingAddress, value: string) => void;
}) {
  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <Input label="Full name" required value={address.name} onChange={(e) => field("name", e.target.value)} />
      <Input label="Phone" required value={address.phone} onChange={(e) => field("phone", e.target.value)} />
      <Input
        label="Address line 1"
        required
        className="sm:col-span-2"
        value={address.line1}
        onChange={(e) => field("line1", e.target.value)}
      />
      <Input
        label="Address line 2"
        className="sm:col-span-2"
        value={address.line2 ?? ""}
        onChange={(e) => field("line2", e.target.value)}
      />
      <Input label="City" required value={address.city} onChange={(e) => field("city", e.target.value)} />
      <Input label="State" required value={address.state} onChange={(e) => field("state", e.target.value)} />
      <Input
        label="Postal code"
        required
        value={address.postal_code}
        onChange={(e) => field("postal_code", e.target.value)}
      />
      <Input label="Country" required value={address.country} onChange={(e) => field("country", e.target.value)} />
    </div>
  );
}
