"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { Search, ShoppingCart, User, Menu, X, ChevronDown } from "lucide-react";
import { useEffect, useState } from "react";
import { cn } from "@/lib/cn";
import { getUser, logout } from "@/lib/auth";
import type { User as AuthUser } from "@/lib/types";
import { useCart } from "@/hooks/useCart";
import { homeLogo } from "@/lib/figma-home";

const nav = [
  { href: "/products", label: "Shop", hasDropdown: true },
  { href: "/products?sort=price_asc", label: "On Sale" },
  { href: "/products?sort=newest", label: "New Arrivals" },
  { href: "/products?category=men", label: "Brands" },
];

export function Header() {
  const pathname = usePathname();
  const [user, setUserState] = useState<AuthUser | null>(null);
  const [menuOpen, setMenuOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [bannerDismissed, setBannerDismissed] = useState(false);
  const { cart, refreshCart } = useCart();

  useEffect(() => {
    setUserState(getUser());
    refreshCart().catch(() => undefined);
  }, [pathname, refreshCart]);

  const itemCount = cart?.items.reduce((n, i) => n + i.quantity, 0) ?? 0;

  function onSearch(e: React.FormEvent) {
    e.preventDefault();
    const q = search.trim();
    window.location.href = q ? `/products?search=${encodeURIComponent(q)}` : "/products";
  }

  async function handleLogout() {
    await logout().catch(() => undefined);
    setUserState(null);
    window.location.href = "/";
  }

  return (
    <header className="sticky top-0 z-40 bg-white">
      {!bannerDismissed ? (
        <div className="relative bg-black px-4 py-2.5 text-center text-xs text-white sm:text-sm">
          Sign up and get 20% off to your first order.{" "}
          <Link href="/register" className="font-medium underline underline-offset-2">
            Sign Up Now
          </Link>
          <button
            type="button"
            aria-label="Dismiss"
            className="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white"
            onClick={() => setBannerDismissed(true)}
          >
            <X className="h-4 w-4" />
          </button>
        </div>
      ) : null}

      <div className="container-shop flex items-center gap-4 py-4 lg:gap-10">
        <button
          type="button"
          className="rounded-lg p-1 lg:hidden"
          onClick={() => setMenuOpen((v) => !v)}
          aria-label="Menu"
        >
          {menuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>

        <Link href="/" className="shrink-0" aria-label="Home">
          <Image
            src={homeLogo}
            alt="SHOP.CO"
            width={159}
            height={24}
            className="h-5 w-auto sm:h-6"
            priority
          />
        </Link>

        <nav className="hidden items-center gap-6 lg:flex">
          {nav.map((item) => (
            <Link
              key={item.label}
              href={item.href}
              className="inline-flex items-center gap-1 text-base text-black transition hover:opacity-70"
            >
              {item.label}
              {item.hasDropdown ? <ChevronDown className="h-4 w-4 opacity-60" /> : null}
            </Link>
          ))}
        </nav>

        <form onSubmit={onSearch} className="ml-auto hidden max-w-[577px] flex-1 md:flex">
          <div className="relative w-full">
            <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-black/40" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search for products..."
              className="w-full rounded-full bg-surface py-3 pl-12 pr-4 text-sm outline-none placeholder:text-black/40"
            />
          </div>
        </form>

        <div className="ml-auto flex items-center gap-3 md:ml-0">
          <button
            type="button"
            className="p-1 md:hidden"
            aria-label="Search"
            onClick={() => setSearchOpen((v) => !v)}
          >
            <Search className="h-5 w-5" />
          </button>
          <Link href="/cart" className="relative p-1" aria-label="Cart">
            <ShoppingCart className="h-5 w-5" />
            {itemCount > 0 ? (
              <span className="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-black px-1 text-[10px] font-bold text-white">
                {itemCount}
              </span>
            ) : null}
          </Link>
          {user ? (
            <div className="flex items-center gap-2">
              <Link href="/account" className="p-1" aria-label="My account">
                <User className="h-5 w-5" />
              </Link>
              <Link href="/account" className="hidden max-w-[120px] truncate text-sm text-black/70 sm:inline">
                {user.name.split(" ")[0]}
              </Link>
              <button type="button" onClick={handleLogout} className="hidden text-sm text-black/60 sm:inline">
                Log out
              </button>
            </div>
          ) : (
            <Link href="/login" className="p-1" aria-label="Sign in">
              <User className="h-5 w-5" />
            </Link>
          )}
        </div>
      </div>

      {searchOpen ? (
        <form onSubmit={onSearch} className="border-t border-border px-4 py-3 md:hidden">
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search for products..."
            className="w-full rounded-full bg-surface px-4 py-3 text-sm outline-none"
          />
        </form>
      ) : null}

      {menuOpen ? (
        <div className="border-t border-border bg-white px-4 py-4 lg:hidden">
          <div className="flex flex-col gap-3">
            {nav.map((item) => (
              <Link
                key={item.label}
                href={item.href}
                className={cn("py-1 text-base", pathname.startsWith("/products") && "font-medium")}
                onClick={() => setMenuOpen(false)}
              >
                {item.label}
              </Link>
            ))}
            {user ? (
              <>
                <Link href="/account" className="py-1 text-base" onClick={() => setMenuOpen(false)}>
                  My account
                </Link>
                <Link href="/account/orders" className="py-1 text-base" onClick={() => setMenuOpen(false)}>
                  Orders
                </Link>
                <Link href="/cart" className="py-1 text-base" onClick={() => setMenuOpen(false)}>
                  Cart
                </Link>
              </>
            ) : null}
          </div>
        </div>
      ) : null}
    </header>
  );
}
