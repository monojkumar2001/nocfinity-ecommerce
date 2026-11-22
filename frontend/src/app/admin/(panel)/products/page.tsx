"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Category, PaginationMeta, Product } from "@/lib/types";
import { formatCurrency } from "@/lib/format";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Spinner } from "@/components/ui/Spinner";
import { Can } from "@/components/admin/Can";
import { Pagination } from "@/components/admin/Pagination";

function flattenCategories(categories: Category[], depth = 0): { id: number; label: string }[] {
  const rows: { id: number; label: string }[] = [];
  for (const c of categories) {
    rows.push({ id: c.id, label: `${"— ".repeat(depth)}${c.name}` });
    if (c.children?.length) rows.push(...flattenCategories(c.children, depth + 1));
  }
  return rows;
}

export default function AdminProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("");
  const [page, setPage] = useState(1);

  useEffect(() => {
    apiRequest<Category[]>("/admin/categories?tree=1")
      .then(({ data }) => setCategories(data))
      .catch(() => undefined);
  }, []);

  const load = useCallback(() => {
    setLoading(true);
    apiRequest<Product[]>(
      `/admin/products${buildQuery({
        per_page: 15,
        page,
        search: search.trim() || undefined,
        category: category || undefined,
        active_only: "0",
      })}`,
    )
      .then(({ data, meta: m }) => {
        setProducts(data);
        setMeta(m ?? null);
      })
      .finally(() => setLoading(false));
  }, [page, search, category]);

  useEffect(() => {
    load();
  }, [load]);

  async function remove(id: number) {
    if (!confirm("Delete this product?")) return;
    await apiRequest(`/admin/products/${id}`, { method: "DELETE" });
    load();
  }

  const categoryOptions = flattenCategories(categories);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="font-display text-2xl font-bold">Products</h1>
        <Can permission="products.create">
          <Link href="/admin/products/new">
            <Button>
              <Plus className="h-4 w-4" />
              New product
            </Button>
          </Link>
        </Can>
      </div>

      <form
        className="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-white p-4"
        onSubmit={(e) => {
          e.preventDefault();
          setPage(1);
          load();
        }}
      >
        <Input
          label="Search"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Name, SKU…"
          className="min-w-[180px] flex-1"
        />
        <Select label="Category" value={category} onChange={(e) => { setCategory(e.target.value); setPage(1); }}>
          <option value="">All categories</option>
          {categoryOptions.map((c) => (
            <option key={c.id} value={c.id}>
              {c.label}
            </option>
          ))}
        </Select>
        <Button type="submit" variant="secondary">
          Filter
        </Button>
      </form>

      {loading ? (
        <Spinner className="mt-12" />
      ) : (
        <>
          <div className="mt-6 overflow-x-auto rounded-xl border border-border bg-white">
            <table className="w-full text-sm">
              <thead className="border-b border-border bg-surface text-left text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">Name</th>
                  <th className="px-4 py-3">SKU</th>
                  <th className="px-4 py-3">Category</th>
                  <th className="px-4 py-3">Price</th>
                  <th className="px-4 py-3">Stock</th>
                  <th className="px-4 py-3">Active</th>
                  <th className="px-4 py-3" />
                </tr>
              </thead>
              <tbody>
                {products.map((p) => (
                  <tr key={p.id} className="border-b border-border last:border-0">
                    <td className="px-4 py-3 font-medium">{p.name}</td>
                    <td className="px-4 py-3 text-muted-foreground">{p.sku}</td>
                    <td className="px-4 py-3 text-muted-foreground">{p.category?.name ?? "—"}</td>
                    <td className="px-4 py-3">
                      {formatCurrency(p.price)}
                      {p.compare_at_price ? (
                        <span className="ml-2 text-xs text-muted-foreground line-through">
                          {formatCurrency(p.compare_at_price)}
                        </span>
                      ) : null}
                    </td>
                    <td className="px-4 py-3">{p.stock}</td>
                    <td className="px-4 py-3">{p.is_active ? "Yes" : "No"}</td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-2">
                        <Can permission="products.edit">
                          <Link href={`/admin/products/${p.id}`} className="inline-flex text-accent hover:underline">
                            <Pencil className="h-4 w-4" />
                          </Link>
                        </Can>
                        <Can permission="products.delete">
                          <button type="button" className="text-sale" onClick={() => remove(p.id)}>
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </Can>
                      </div>
                    </td>
                  </tr>
                ))}
                {!products.length ? (
                  <tr>
                    <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                      No products found.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
