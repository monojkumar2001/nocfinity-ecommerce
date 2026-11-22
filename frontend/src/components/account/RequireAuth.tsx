"use client";

import { useEffect, useState, type ReactNode } from "react";
import { usePathname, useRouter } from "next/navigation";
import { getToken } from "@/lib/auth";
import { Spinner } from "@/components/ui/Spinner";

/**
 * Always keep `{children}` in the tree so Next.js instant-navigation
 * validation can see nested account segments.
 */
export function RequireAuth({ children }: { children: ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ready, setReady] = useState(false);
  const [allowed, setAllowed] = useState(false);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      router.replace(`/login?next=${encodeURIComponent(pathname)}`);
      setAllowed(false);
      setReady(true);
      return;
    }
    setAllowed(true);
    setReady(true);
  }, [pathname, router]);

  const showContent = ready && allowed;

  return (
    <>
      {!showContent ? (
        <div className="flex justify-center py-16">
          <Spinner className="h-8 w-8" />
        </div>
      ) : null}
      <div hidden={!showContent} className={showContent ? undefined : "hidden"}>
        {children}
      </div>
    </>
  );
}
