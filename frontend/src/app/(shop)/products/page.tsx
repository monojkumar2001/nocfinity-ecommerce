"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { ArrowLeft, ArrowRight, ChevronDown, SlidersHorizontal } from "lucide-react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Category, Product, PaginationMeta } from "@/lib/types";
import { ProductCard } from "@/components/shop/ProductCard";
import { FiltersSidebar, type FiltersState } from "@/components/shop/FiltersSidebar";
import { Spinner } from "@/components/ui/Spinner";

type QueryState = {
  search: string;
  category: string;
  sort: string;
  page: number;
  min_price: string;
  max_price: string;
  color: string;
  size: string;
  dress_style: string;
};

function parseQuery(): QueryState {
  const sp = typeof window === "undefined" ? new URLSearchParams() : new URLSearchParams(window.location.search);
  return {
    search: sp.get("search") ?? "",
    category: sp.get("category") ?? "",
    sort: sp.get("sort") ?? "newest",
    page: Number(sp.get("page") ?? "1"),
    min_price: sp.get("min_price") ?? "50",
    max_price: sp.get("max_price") ?? "5000",
    color: sp.get("color") ?? "",
    size: sp.get("size") ?? "",
    dress_style: sp.get("dress_style") ?? sp.get("style") ?? "",
  };
}

export default function ProductsPage() {
  const router = useRouter();
  const [mounted, setMounted] = useState(false);
  const [query, setQuery] = useState<QueryState>({
    search: "",
    category: "",
    sort: "newest",
    page: 1,
    min_price: "50",
    max_price: "5000",
    color: "",
    size: "",
    dress_style: "",
  });
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [priceMax, setPriceMax] = useState(5000);
  const [draft, setDraft] = useState<FiltersState>({
    category: "",
    min_price: "50",
    max_price: "5000",
    color: "",
    size: "",
    dress_style: "",
  });

  useEffect(() => {
    const sync = () => {
      const next = parseQuery();
      setQuery(next);
      setPriceMax(Number(next.max_price) || 5000);
      setDraft({
        category: next.category,
        min_price: next.min_price,
        max_price: next.max_price,
        color: next.color,
        size: next.size,
        dress_style: next.dress_style,
      });
    };
    sync();
    setMounted(true);
    window.addEventListener("popstate", sync);
    return () => window.removeEventListener("popstate", sync);
  }, []);

  const loadProducts = useCallback(async () => {
    setLoading(true);
    try {
      const qs = buildQuery({
        search: query.search,
        category: query.category,
        sort: query.sort,
        min_price: query.min_price,
        max_price: query.max_price,
        color: query.color || undefined,
        size: query.size || undefined,
        dress_style: query.dress_style || undefined,
        page: query.page,
        per_page: 9,
      });
      const { data, meta: m } = await apiRequest<Product[]>(`/products${qs}`);
      setProducts(data ?? []);
      setMeta(m ?? null);
    } catch {
      setProducts([]);
      setMeta(null);
    } finally {
      setLoading(false);
    }
  }, [query]);

  useEffect(() => {
    if (!mounted) return;
    void loadProducts();
  }, [mounted, loadProducts]);

  useEffect(() => {
    if (!mounted) return;
    apiRequest<Category[]>("/categories")
      .then((res) => setCategories(res.data ?? []))
      .catch(() => undefined);
  }, [mounted]);

  function push(next: Record<string, string | number | undefined>) {
    const qs = buildQuery({
      search: query.search,
      category: query.category,
      sort: query.sort,
      min_price: query.min_price,
      max_price: query.max_price,
      color: query.color,
      size: query.size,
      dress_style: query.dress_style,
      page: 1,
      ...next,
    });
    router.push(`/products${qs}`);
    const merged = { ...query, page: 1, ...Object.fromEntries(
      Object.entries(next).map(([k, v]) => [k, v == null ? "" : String(v)]),
    ) } as QueryState;
    if (typeof next.page === "number") merged.page = next.page;
    if (typeof next.sort === "string") merged.sort = next.sort;
    if (typeof next.max_price === "number" || typeof next.max_price === "string") {
      merged.max_price = String(next.max_price);
    }
    setQuery((prev) => {
      const updated = { ...prev, ...merged };
      setPriceMax(Number(updated.max_price) || 5000);
      setDraft({
        category: updated.category,
        min_price: updated.min_price,
        max_price: updated.max_price,
        color: updated.color,
        size: updated.size,
        dress_style: updated.dress_style,
      });
      return updated;
    });
  }

  const flatCategories = useMemo(
    () =>
      categories.flatMap((c) => [
        ...(c.children?.length
          ? c.children.map((child) => ({ id: child.id, name: child.name, slug: child.slug }))
          : [{ id: c.id, name: c.name, slug: c.slug }]),
      ]),
    [categories],
  );

  const title =
    flatCategories.find((c) => c.slug === query.category)?.name ??
    query.dress_style ??
    (query.search ? `Results for “${query.search}”` : "All Products");

  const showingFrom = meta ? (meta.current_page - 1) * meta.per_page + 1 : 0;
  const showingTo = meta ? Math.min(meta.current_page * meta.per_page, meta.total) : 0;

  const pageNumbers = (() => {
    if (!meta) return [] as number[];
    const total = meta.last_page;
    const current = meta.current_page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set<number>([1, 2, total - 1, total, current - 1, current, current + 1]);
    return [...pages].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
  })();

  function applyFilters() {
    push({
      category: draft.category || undefined,
      min_price: 50,
      max_price: priceMax,
      color: draft.color || undefined,
      size: draft.size || undefined,
      dress_style: draft.dress_style || undefined,
      page: 1,
    });
    setFiltersOpen(false);
  }

  if (!mounted) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  const filtersPanel = (
    <FiltersSidebar
      categories={flatCategories}
      filters={draft}
      priceMax={priceMax}
      onPriceMaxChange={setPriceMax}
      onChange={(patch) => setDraft((d) => ({ ...d, ...patch }))}
      onApply={applyFilters}
      onClose={filtersOpen ? () => setFiltersOpen(false) : undefined}
    />
  );

  return (
    <div className="container-shop py-6 sm:py-8">
      <p className="text-sm font-normal text-black/60">
        <Link href="/" className="hover:text-black">
          Home
        </Link>{" "}
        &gt; <span className="text-black">{title}</span>
      </p>

      <div className="mt-5 grid gap-5 lg:grid-cols-[295px_1fr]">
        <div className="hidden lg:block">{filtersPanel}</div>

        <div>
          <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 className="font-display text-[32px] font-bold uppercase leading-none">{title}</h1>
            <div className="flex flex-wrap items-center gap-3 text-sm font-normal text-black/60">
              {meta ? (
                <span>
                  Showing {showingFrom}-{showingTo} of {meta.total} Products
                </span>
              ) : null}
              <label className="inline-flex items-center gap-1">
                <span>Sort by:</span>
                <span className="relative inline-flex items-center text-black">
                  <select
                    value={query.sort}
                    onChange={(e) => push({ sort: e.target.value })}
                    className="appearance-none bg-transparent pr-5 font-normal outline-none"
                  >
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                  </select>
                  <ChevronDown className="pointer-events-none absolute right-0 h-4 w-4" />
                </span>
              </label>
              <button
                type="button"
                className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-surface lg:hidden"
                onClick={() => setFiltersOpen(true)}
                aria-label="Open filters"
              >
                <SlidersHorizontal className="h-4 w-4" />
              </button>
            </div>
          </div>

          {loading ? (
            <div className="flex justify-center py-24">
              <Spinner className="h-8 w-8" />
            </div>
          ) : products.length === 0 ? (
            <p className="py-20 text-center text-black/60">No products match your filters.</p>
          ) : (
            <div className="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 md:gap-x-5 md:gap-y-9">
              {products.map((p) => (
                <ProductCard key={p.id} product={p} />
              ))}
            </div>
          )}

          {meta && meta.last_page > 1 ? (
            <div className="mt-8 flex items-center justify-between gap-2 border-t border-black/10 pt-5">
              <button
                type="button"
                disabled={query.page <= 1}
                onClick={() => push({ page: query.page - 1 })}
                className="inline-flex items-center gap-2 rounded-lg border border-black/10 px-3.5 py-2 text-sm font-normal disabled:opacity-40"
              >
                <ArrowLeft className="h-4 w-4" /> Previous
              </button>
              <div className="flex items-center gap-1">
                {pageNumbers.map((n, idx) => {
                  const prev = pageNumbers[idx - 1];
                  const showDots = prev != null && n - prev > 1;
                  return (
                    <span key={n} className="contents">
                      {showDots ? <span className="px-1 text-black/40">…</span> : null}
                      <button
                        type="button"
                        onClick={() => push({ page: n })}
                        className={`h-9 min-w-9 rounded-lg px-2 text-sm font-normal ${
                          n === query.page ? "bg-surface text-black" : "text-black/50 hover:bg-surface"
                        }`}
                      >
                        {n}
                      </button>
                    </span>
                  );
                })}
              </div>
              <button
                type="button"
                disabled={query.page >= meta.last_page}
                onClick={() => push({ page: query.page + 1 })}
                className="inline-flex items-center gap-2 rounded-lg border border-black/10 px-3.5 py-2 text-sm font-normal disabled:opacity-40"
              >
                Next <ArrowRight className="h-4 w-4" />
              </button>
            </div>
          ) : null}
        </div>
      </div>

      {filtersOpen ? (
        <div className="fixed inset-0 z-50 bg-black/40 p-4 lg:hidden">
          <div className="mx-auto max-h-[92vh] max-w-md overflow-y-auto">{filtersPanel}</div>
        </div>
      ) : null}
    </div>
  );
}
