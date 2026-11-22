"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { apiRequest, buildQuery } from "@/lib/api";
import type { PaginationMeta } from "@/lib/types";
import { formatDate } from "@/lib/format";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { Pagination } from "@/components/admin/Pagination";

type CustomerRow = {
  id: number;
  name: string;
  email: string;
  orders_count?: number;
  created_at?: string;
};

export default function AdminCustomersPage() {
  const [customers, setCustomers] = useState<CustomerRow[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);

  const load = useCallback(() => {
    setLoading(true);
    apiRequest<CustomerRow[]>(
      `/admin/customers${buildQuery({
        per_page: 20,
        page,
        search: search.trim() || undefined,
      })}`,
    )
      .then(({ data, meta: m }) => {
        setCustomers(data);
        setMeta(m ?? null);
      })
      .finally(() => setLoading(false));
  }, [page, search]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Customers</h1>

      <form
        className="mt-6 flex max-w-xl flex-wrap items-end gap-3 rounded-xl border border-border bg-white p-4"
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
          placeholder="Name or email"
          className="min-w-[200px] flex-1"
        />
        <Button type="submit" variant="secondary">
          Search
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
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Orders</th>
                  <th className="px-4 py-3">Joined</th>
                </tr>
              </thead>
              <tbody>
                {customers.map((c) => (
                  <tr key={c.id} className="border-b border-border">
                    <td className="px-4 py-3">
                      <Link href={`/admin/customers/${c.id}`} className="font-medium text-accent hover:underline">
                        {c.name}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{c.email}</td>
                    <td className="px-4 py-3">{c.orders_count ?? 0}</td>
                    <td className="px-4 py-3 text-muted-foreground">{formatDate(c.created_at)}</td>
                  </tr>
                ))}
                {!customers.length ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                      No customers found.
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
