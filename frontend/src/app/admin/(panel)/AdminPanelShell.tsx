"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { Menu } from "lucide-react";
import { AdminSidebar } from "@/components/admin/AdminSidebar";
import { fetchCurrentUser, getToken, getUser, hasPermission, isStaff } from "@/lib/auth";
import { firstAllowedAdminPath, requiredPermissionForPath } from "@/lib/admin-routes";
import type { User } from "@/lib/types";
import { Spinner } from "@/components/ui/Spinner";

export function AdminPanelShell({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);
  const [navOpen, setNavOpen] = useState(false);

  useEffect(() => {
    setNavOpen(false);
  }, [pathname]);

  useEffect(() => {
    let cancelled = false;

    async function boot() {
      if (!getToken()) {
        router.replace(`/admin/login?next=${encodeURIComponent(pathname)}`);
        return;
      }

      let current = getUser();
      if (!current || !isStaff(current)) {
        try {
          current = await fetchCurrentUser();
        } catch {
          router.replace("/admin/login");
          return;
        }
      }

      if (!isStaff(current)) {
        router.replace("/admin/login");
        return;
      }

      const needed = requiredPermissionForPath(pathname);
      if (needed && !hasPermission(current, needed)) {
        router.replace(firstAllowedAdminPath(current.permissions));
        return;
      }

      if (!cancelled) {
        setUser(current);
        setReady(true);
      }
    }

    setReady(false);
    void boot();
    return () => {
      cancelled = true;
    };
  }, [pathname, router]);

  if (!ready || !user) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-surface">
      <AdminSidebar user={user} open={navOpen} onClose={() => setNavOpen(false)} />
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-white px-4 py-3 md:hidden">
          <button
            type="button"
            className="rounded-lg border border-border p-2"
            onClick={() => setNavOpen(true)}
            aria-label="Open menu"
          >
            <Menu className="h-5 w-5" />
          </button>
          <div className="min-w-0">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src="/images/home/logo.png" alt="AURA" className="h-5 w-auto invert" />
            <p className="truncate text-xs text-muted-foreground">{user.name}</p>
          </div>
        </header>
        <main className="flex-1 overflow-auto p-4 md:p-8">{children}</main>
      </div>
    </div>
  );
}
