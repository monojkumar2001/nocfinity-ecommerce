"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Plus, Trash2 } from "lucide-react";
import { apiRequest } from "@/lib/api";
import { DRESS_STYLES, PRODUCT_COLORS, PRODUCT_SIZES } from "@/lib/product-filters";
import type { Category, Product } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

type FormState = {
  category_id: string;
  name: string;
  sku: string;
  description: string;
  price: string;
  compare_at_price: string;
  stock: string;
  color: string;
  size: string;
  dress_style: string;
  is_active: boolean;
  image_urls: string[];
};

const empty: FormState = {
  category_id: "",
  name: "",
  sku: "",
  description: "",
  price: "",
  compare_at_price: "",
  stock: "0",
  color: "",
  size: "",
  dress_style: "",
  is_active: true,
  image_urls: [""],
};

function flattenCategories(categories: Category[], depth = 0): { id: number; label: string }[] {
  const rows: { id: number; label: string }[] = [];
  for (const c of categories) {
    rows.push({ id: c.id, label: `${"— ".repeat(depth)}${c.name}` });
    if (c.children?.length) {
      rows.push(...flattenCategories(c.children, depth + 1));
    }
  }
  return rows;
}

export function ProductForm({ productId }: { productId?: number }) {
  const router = useRouter();
  const [form, setForm] = useState<FormState>(empty);
  const [categories, setCategories] = useState<Category[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(!!productId);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    apiRequest<Category[]>("/admin/categories?tree=1")
      .then(({ data }) => setCategories(data))
      .catch(() =>
        apiRequest<Category[]>("/admin/categories?tree=0&per_page=50").then(({ data }) => setCategories(data)),
      );
  }, []);

  useEffect(() => {
    if (!productId) return;
    apiRequest<Product>(`/admin/products/${productId}`)
      .then(({ data: p }) => {
        const urls = (p.images ?? []).map((img) => img.url).filter(Boolean);
        setForm({
          category_id: String(p.category?.id ?? ""),
          name: p.name,
          sku: p.sku,
          description: p.description ?? "",
          price: String(p.price),
          compare_at_price: p.compare_at_price ? String(p.compare_at_price) : "",
          stock: String(p.stock),
          color: p.color ?? "",
          size: p.size ?? "",
          dress_style: p.dress_style ?? "",
          is_active: p.is_active,
          image_urls: urls.length ? urls : [p.thumbnail ?? ""],
        });
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Failed to load product"))
      .finally(() => setLoading(false));
  }, [productId]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSaving(true);
    const image_urls = form.image_urls.map((u) => u.trim()).filter(Boolean);
    const body = {
      category_id: Number(form.category_id),
      name: form.name,
      sku: form.sku,
      description: form.description || null,
      price: Number(form.price),
      compare_at_price: form.compare_at_price ? Number(form.compare_at_price) : null,
      stock: Number(form.stock),
      color: form.color || null,
      size: form.size || null,
      dress_style: form.dress_style || null,
      is_active: form.is_active,
      image_urls,
    };
    try {
      if (productId) {
        await apiRequest(`/admin/products/${productId}`, {
          method: "PUT",
          body: JSON.stringify(body),
        });
      } else {
        await apiRequest("/admin/products", { method: "POST", body: JSON.stringify(body) });
      }
      router.push("/admin/products");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <Spinner className="mt-8" />;

  const options = flattenCategories(categories);

  return (
    <form onSubmit={handleSubmit} className="max-w-2xl space-y-4 rounded-xl border border-border bg-white p-6">
      <Select
        label="Category"
        required
        value={form.category_id}
        onChange={(e) => setForm((f) => ({ ...f, category_id: e.target.value }))}
      >
        <option value="">Select category</option>
        {options.map((c) => (
          <option key={c.id} value={c.id}>
            {c.label}
          </option>
        ))}
      </Select>
      <Input label="Name" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
      <Input label="SKU" required value={form.sku} onChange={(e) => setForm((f) => ({ ...f, sku: e.target.value }))} />
      <label className="flex flex-col gap-1.5 text-sm">
        <span className="font-medium">Description</span>
        <textarea
          className="rounded-lg border border-border px-3 py-2"
          rows={4}
          value={form.description}
          onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
        />
      </label>
      <div className="grid gap-4 sm:grid-cols-3">
        <Input
          label="Price"
          type="number"
          step="0.01"
          required
          value={form.price}
          onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
        />
        <Input
          label="Discount / compare-at"
          type="number"
          step="0.01"
          value={form.compare_at_price}
          onChange={(e) => setForm((f) => ({ ...f, compare_at_price: e.target.value }))}
        />
        <Input
          label="Stock"
          type="number"
          required
          value={form.stock}
          onChange={(e) => setForm((f) => ({ ...f, stock: e.target.value }))}
        />
      </div>
      <div className="grid gap-4 sm:grid-cols-3">
        <Select
          label="Color"
          value={form.color}
          onChange={(e) => setForm((f) => ({ ...f, color: e.target.value }))}
        >
          <option value="">Select color</option>
          {PRODUCT_COLORS.map((c) => (
            <option key={c.name} value={c.name}>
              {c.name}
            </option>
          ))}
        </Select>
        <Select
          label="Size"
          value={form.size}
          onChange={(e) => setForm((f) => ({ ...f, size: e.target.value }))}
        >
          <option value="">Select size</option>
          {PRODUCT_SIZES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </Select>
        <Select
          label="Dress style"
          value={form.dress_style}
          onChange={(e) => setForm((f) => ({ ...f, dress_style: e.target.value }))}
        >
          <option value="">Select style</option>
          {DRESS_STYLES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </Select>
      </div>

      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <p className="text-sm font-medium">Images</p>
          <Button
            type="button"
            size="sm"
            variant="secondary"
            onClick={() => setForm((f) => ({ ...f, image_urls: [...f.image_urls, ""] }))}
          >
            <Plus className="h-4 w-4" />
            Add image URL
          </Button>
        </div>
        {form.image_urls.map((url, index) => (
          <div key={index} className="flex gap-2">
            <Input
              label={index === 0 ? "Primary image URL" : `Image URL ${index + 1}`}
              value={url}
              onChange={(e) =>
                setForm((f) => {
                  const next = [...f.image_urls];
                  next[index] = e.target.value;
                  return { ...f, image_urls: next };
                })
              }
              placeholder="https://..."
              className="flex-1"
            />
            {form.image_urls.length > 1 ? (
              <div className="flex items-end pb-0.5">
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  onClick={() =>
                    setForm((f) => ({
                      ...f,
                      image_urls: f.image_urls.filter((_, i) => i !== index),
                    }))
                  }
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            ) : null}
          </div>
        ))}
        {form.image_urls[0] ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={form.image_urls[0]} alt="" className="mt-2 h-24 w-24 rounded-lg object-cover" />
        ) : null}
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={form.is_active}
          onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
        />
        Active
      </label>
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
      <Button type="submit" disabled={saving}>
        {saving ? "Saving…" : productId ? "Update product" : "Create product"}
      </Button>
    </form>
  );
}
