"use client";

import { useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { Spinner } from "@/components/ui/Spinner";

/** Legacy route — send customers to the account order detail page. */
export default function OrderDetailRedirectPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();

  useEffect(() => {
    router.replace(`/account/orders/${id}`);
  }, [id, router]);

  return (
    <div className="flex justify-center py-24">
      <Spinner className="h-8 w-8" />
    </div>
  );
}
