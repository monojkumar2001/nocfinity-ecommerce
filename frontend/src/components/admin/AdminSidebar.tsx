"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  Package,
  FolderTree,
  ShoppingCart,
  Ticket,
  Users,
  Shield,
  Settings,
  LogOut,
  X,
} from "lucide-react";
import { cn } from "@/lib/cn";
import { clearAuth, getUser, hasPermission, isStaff } from "@/lib/auth";
import type { User } from "@/lib/types";

const links = [
  { href: "/admin", label: "Dashboard", icon: LayoutDashboard, permission: "reports.view" },
  { href: "/admin/products", label: "Products", icon: Package, permission: "products.view" },
  { href: "/admin/categories", label: "Categories", icon: FolderTree, permission: "categories.view" },
  { href: "/admin/orders", label: "Orders", icon: ShoppingCart, permission: "orders.view" },
  { href: "/admin/coupons", label: "Coupons", icon: Ticket, permission: "coupons.manage" },
  { href: "/admin/customers", label: "Customers", icon: Users, permission: "customers.view" },
  { href: "/admin/roles", label: "Roles & staff", icon: Shield, permission: "roles.manage" },
  { href: "/admin/profile", label: "Settings", icon: Settings },
];

export function AdminSidebar({
  user,
  open,
  onClose,
}: {
  user: User;
  open?: boolean;
  onClose?: () => void;
}) {
  const pathname = usePathname();

  function visible(link: (typeof links)[0]) {
    if (!link.permission) return true;
    return hasPermission(user, link.permission);
  }

  return (
    <>
      <div
        className={cn(
          "fixed inset-0 z-40 bg-black/40 transition md:hidden",
          open ? "opacity-100" : "pointer-events-none opacity-0",
        )}
        onClick={onClose}
        aria-hidden={!open}
      />
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 flex w-60 shrink-0 flex-col border-r border-border bg-white transition-transform md:static md:translate-x-0",
          open ? "translate-x-0" : "-translate-x-full md:translate-x-0",
        )}
      >
        <div className="flex items-center justify-between gap-2 border-b border-border px-4 py-4">
          <Link href="/admin" className="flex min-w-0 items-center" onClick={onClose}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="/images/home/logo.png"
              alt="AURA"
              className="h-7 w-auto invert"
            />
          </Link>
          <button type="button" className="rounded-lg p-1 text-muted-foreground hover:bg-surface md:hidden" onClick={onClose}>
            <X className="h-5 w-5" />
          </button>
        </div>
        <nav className="flex-1 space-y-0.5 overflow-y-auto p-3">
          {links.filter(visible).map((link) => {
            const active = pathname === link.href || (link.href !== "/admin" && pathname.startsWith(link.href));
            return (
              <Link
                key={link.href}
                href={link.href}
                onClick={onClose}
                className={cn(
                  "flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition",
                  active ? "bg-accent-soft text-accent" : "text-muted-foreground hover:bg-surface hover:text-foreground",
                )}
              >
                <link.icon className="h-4 w-4" />
                {link.label}
              </Link>
            );
          })}
        </nav>
        <div className="border-t border-border p-3">
          <p className="truncate px-3 text-xs text-muted-foreground">{user.name}</p>
          <p className="truncate px-3 text-[11px] text-muted-foreground">{user.email}</p>
          <button
            type="button"
            className="mt-2 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-surface"
            onClick={() => {
              clearAuth();
              window.location.href = "/admin/login";
            }}
          >
            <LogOut className="h-4 w-4" />
            Sign out
          </button>
        </div>
      </aside>
    </>
  );
}

export function useAdminGuard() {
  const user = getUser();
  if (!user || !isStaff(user)) return null;
  return user;
}
