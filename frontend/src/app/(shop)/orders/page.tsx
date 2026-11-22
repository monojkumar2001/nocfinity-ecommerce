"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Spinner } from "@/components/ui/Spinner";

/** Legacy route — send customers to the account orders hub. */
export default function OrdersRedirectPage() {
  const router = useRouter();

  useEffect(() => {
    router.replace("/account/orders");
  }, [router]);

  return (
    <div className="flex justify-center py-24">
      <Spinner className="h-8 w-8" />
    </div>
  );
}
