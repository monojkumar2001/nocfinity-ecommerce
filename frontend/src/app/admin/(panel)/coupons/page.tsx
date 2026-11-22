"use client";

import { useCallback, useEffect, useState } from "react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Coupon, PaginationMeta } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { Can } from "@/components/admin/Can";
import { Pagination } from "@/components/admin/Pagination";

type FormState = {
  code: string;
  type: "percentage" | "fixed";
  value: string;
  minimum_order_amount: string;
  usage_limit: string;
  starts_at: string;
  expires_at: string;
  is_active: boolean;
};

const empty: FormState = {
  code: "",
  type: "percentage",
  value: "10",
  minimum_order_amount: "0",
  usage_limit: "",
  starts_at: "",
  expires_at: "",
  is_active: true,
};

function toDateInput(value: string | null | undefined) {
  if (!value) return "";
  return value.slice(0, 10);
}

export default function AdminCouponsPage() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState<FormState>(empty);
  const [editing, setEditing] = useState<Coupon | null>(null);
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    apiRequest<Coupon[]>(`/admin/coupons${buildQuery({ per_page: 20, page })}`)
      .then(({ data, meta: m }) => {
        setCoupons(data);
        setMeta(m ?? null);
      })
      .finally(() => setLoading(false));
  }, [page]);

  useEffect(() => {
    load();
  }, [load]);

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    const body = {
      code: form.code,
      type: form.type,
      value: Number(form.value),
      minimum_order_amount: Number(form.minimum_order_amount || 0),
      usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
      starts_at: form.starts_at || null,
      expires_at: form.expires_at || null,
      is_active: form.is_active,
    };
    try {
      if (editing) {
        await apiRequest(`/admin/coupons/${editing.id}`, { method: "PUT", body: JSON.stringify(body) });
      } else {
        await apiRequest("/admin/coupons", { method: "POST", body: JSON.stringify(body) });
      }
      setForm(empty);
      setEditing(null);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
    }
  }

  async function remove(id: number) {
    if (!confirm("Delete this coupon?")) return;
    await apiRequest(`/admin/coupons/${id}`, { method: "DELETE" });
    load();
  }

  function startEdit(c: Coupon) {
    setEditing(c);
    setForm({
      code: c.code,
      type: c.type,
      value: String(c.value),
      minimum_order_amount: String(c.minimum_order_amount),
      usage_limit: c.usage_limit != null ? String(c.usage_limit) : "",
      starts_at: toDateInput(c.starts_at),
      expires_at: toDateInput(c.expires_at),
      is_active: c.is_active,
    });
  }

  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Coupons</h1>

      <Can permission="coupons.manage">
        <form onSubmit={save} className="mt-6 grid max-w-3xl gap-3 rounded-xl border border-border bg-white p-4 sm:grid-cols-2">
          <Input label="Code" required value={form.code} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} />
          <Select
            label="Type"
            value={form.type}
            onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as FormState["type"] }))}
          >
            <option value="percentage">Percentage</option>
            <option value="fixed">Fixed amount</option>
          </Select>
          <Input
            label="Value"
            type="number"
            required
            value={form.value}
            onChange={(e) => setForm((f) => ({ ...f, value: e.target.value }))}
          />
          <Input
            label="Min order amount"
            type="number"
            value={form.minimum_order_amount}
            onChange={(e) => setForm((f) => ({ ...f, minimum_order_amount: e.target.value }))}
          />
          <Input
            label="Usage limit"
            type="number"
            value={form.usage_limit}
            onChange={(e) => setForm((f) => ({ ...f, usage_limit: e.target.value }))}
            placeholder="Unlimited"
          />
          <label className="flex items-end gap-2 pb-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
            />
            Active
          </label>
          <Input
            label="Starts at"
            type="date"
            value={form.starts_at}
            onChange={(e) => setForm((f) => ({ ...f, starts_at: e.target.value }))}
          />
          <Input
            label="Expires at"
            type="date"
            value={form.expires_at}
            onChange={(e) => setForm((f) => ({ ...f, expires_at: e.target.value }))}
          />
          <div className="flex gap-2 sm:col-span-2">
            <Button type="submit">{editing ? "Update coupon" : "Create coupon"}</Button>
            {editing ? (
              <Button type="button" variant="ghost" onClick={() => { setEditing(null); setForm(empty); }}>
                Cancel
              </Button>
            ) : null}
          </div>
          {error ? <p className="text-sm text-red-600 sm:col-span-2">{error}</p> : null}
        </form>
      </Can>

      {loading ? (
        <Spinner className="mt-8" />
      ) : (
        <>
          <ul className="mt-8 divide-y divide-border rounded-xl border border-border bg-white text-sm">
            {coupons.map((c) => (
              <li key={c.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                  <p className="font-mono font-semibold">
                    {c.code}
                    {!c.is_active ? <span className="ml-2 text-xs font-sans text-muted-foreground">inactive</span> : null}
                  </p>
                  <p className="text-muted-foreground">
                    {c.type} {c.value} · min {c.minimum_order_amount} · used {c.used_count}
                    {c.usage_limit ? ` / ${c.usage_limit}` : ""}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {c.starts_at ? `from ${toDateInput(c.starts_at)}` : "no start"}
                    {" · "}
                    {c.expires_at ? `until ${toDateInput(c.expires_at)}` : "no expiry"}
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button size="sm" variant="secondary" onClick={() => startEdit(c)}>
                    Edit
                  </Button>
                  <Button size="sm" variant="danger" onClick={() => remove(c.id)}>
                    Delete
                  </Button>
                </div>
              </li>
            ))}
            {!coupons.length ? (
              <li className="px-4 py-8 text-center text-muted-foreground">No coupons yet.</li>
            ) : null}
          </ul>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  );
}
