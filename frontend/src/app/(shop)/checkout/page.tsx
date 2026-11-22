"use client";

import { CheckoutForm } from "@/components/shop/CheckoutForm";

export default function CheckoutPage() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <h1 className="font-display text-3xl font-bold">Checkout</h1>
      <p className="mt-1 text-sm text-muted-foreground">Secure payment powered by Stripe</p>
      <div className="mt-8">
        <CheckoutForm />
      </div>
    </div>
  );
}