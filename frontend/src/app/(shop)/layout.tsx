import { Suspense } from "react";
import { CartProvider } from "@/hooks/useCart";
import { Footer } from "@/components/shop/Footer";
import { Header } from "@/components/shop/Header";
import { Spinner } from "@/components/ui/Spinner";

export default function ShopLayout({ children }: { children: React.ReactNode }) {
  return (
    <CartProvider>
      <Suspense
        fallback={
          <div className="flex h-14 items-center justify-center border-b border-border">
            <Spinner />
          </div>
        }
      >
        <Header />
      </Suspense>
      <main className="flex-1">
        <Suspense fallback={<div className="flex justify-center py-24"><Spinner /></div>}>{children}</Suspense>
      </main>
      <Footer />
    </CartProvider>
  );
}