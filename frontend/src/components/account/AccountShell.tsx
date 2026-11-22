"use client";

import type { ReactNode } from "react";
import { AccountNav } from "@/components/account/AccountNav";
import { RequireAuth } from "@/components/account/RequireAuth";

export function AccountShell({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="font-display text-3xl font-bold">My account</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Orders, profile, password, and cart — all in one place.
          </p>
        </div>
        <div className="grid gap-8 lg:grid-cols-[220px_minmax(0,1fr)]">
          <aside>
            <AccountNav />
          </aside>
          <div>{children}</div>
        </div>
      </div>
    </RequireAuth>
  );
}
