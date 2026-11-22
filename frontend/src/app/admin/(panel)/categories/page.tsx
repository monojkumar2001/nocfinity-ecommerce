"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { apiRequest } from "@/lib/api";
import type { Category } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { Can } from "@/components/admin/Can";
import { getUser, hasPermission } from "@/lib/auth";

type FormState = {
  name: string;
  parent_id: string;
  description: string;
  is_active: boolean;
};

const emptyForm: FormState = {
  name: "",
  parent_id: "",
  description: "",
  is_active: true,
};

export default function AdminCategoriesPage() {
  const [tree, setTree] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState<FormState>(emptyForm);
  const [editing, setEditing] = useState<Category | null>(null);
  const [error, setError] = useState<string | null>(null);
  const user = getUser();
  const canCreate = hasPermission(user, "categories.create");
  const canEdit = hasPermission(user, "categories.edit");

  const load = useCallback(() => {
    setLoading(true);
    apiRequest<Category[]>("/admin/categories?tree=1")
      .then(({ data }) => setTree(data))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const parents = useMemo(() => tree.filter((c) => !c.parent_id), [tree]);

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    const body = {
      name: form.name,
      parent_id: form.parent_id ? Number(form.parent_id) : null,
      description: form.description || null,
      is_active: form.is_active,
    };
    try {
      if (editing) {
        await apiRequest(`/admin/categories/${editing.id}`, {
          method: "PUT",
          body: JSON.stringify(body),
        });
      } else {
        await apiRequest("/admin/categories", {
          method: "POST",
          body: JSON.stringify(body),
        });
      }
      setForm(emptyForm);
      setEditing(null);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
    }
  }

  async function remove(id: number) {
    if (!confirm("Delete this category?")) return;
    try {
      await apiRequest(`/admin/categories/${id}`, { method: "DELETE" });
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Delete failed");
    }
  }

  function startEdit(c: Category) {
    setEditing(c);
    setForm({
      name: c.name,
      parent_id: c.parent_id ? String(c.parent_id) : "",
      description: c.description ?? "",
      is_active: c.is_active,
    });
  }

  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Categories</h1>
      <p className="mt-1 text-sm text-muted-foreground">Two-level tree (e.g. Electronics → Phones).</p>

      {(canCreate || (editing && canEdit)) ? (
        <form onSubmit={save} className="mt-6 grid max-w-xl gap-3 rounded-xl border border-border bg-white p-4 sm:grid-cols-2">
          <Input
            label={editing ? "Edit name" : "Category name"}
            value={form.name}
            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            required
            className="sm:col-span-2"
          />
          <Select
            label="Parent (optional)"
            value={form.parent_id}
            onChange={(e) => setForm((f) => ({ ...f, parent_id: e.target.value }))}
          >
            <option value="">Top level</option>
            {parents
              .filter((p) => !editing || p.id !== editing.id)
              .map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name}
                </option>
              ))}
          </Select>
          <label className="flex items-end gap-2 pb-2 text-sm">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
            />
            Active
          </label>
          <label className="flex flex-col gap-1.5 text-sm sm:col-span-2">
            <span className="font-medium">Description</span>
            <textarea
              className="rounded-lg border border-border px-3 py-2"
              rows={2}
              value={form.description}
              onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
            />
          </label>
          <div className="flex gap-2 sm:col-span-2">
            <Button type="submit">{editing ? "Update" : "Create"}</Button>
            {editing ? (
              <Button type="button" variant="ghost" onClick={() => { setEditing(null); setForm(emptyForm); }}>
                Cancel
              </Button>
            ) : null}
          </div>
        </form>
      ) : null}

      {error ? <p className="mt-2 text-sm text-red-600">{error}</p> : null}

      {loading ? (
        <Spinner className="mt-8" />
      ) : (
        <ul className="mt-8 divide-y divide-border rounded-xl border border-border bg-white">
          {tree.map((parent) => (
            <li key={parent.id} className="px-4 py-3">
              <div className="flex items-center justify-between gap-3 text-sm">
                <div>
                  <p className="font-medium">{parent.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {parent.slug} · {parent.is_active ? "active" : "inactive"}
                  </p>
                </div>
                <div className="flex gap-2">
                  <Can permission="categories.edit">
                    <Button size="sm" variant="secondary" onClick={() => startEdit(parent)}>
                      Edit
                    </Button>
                  </Can>
                  <Can permission="categories.delete">
                    <Button size="sm" variant="danger" onClick={() => remove(parent.id)}>
                      Delete
                    </Button>
                  </Can>
                </div>
              </div>
              {parent.children?.length ? (
                <ul className="mt-3 space-y-2 border-l border-border pl-4">
                  {parent.children.map((child) => (
                    <li key={child.id} className="flex items-center justify-between gap-3 text-sm">
                      <div>
                        <p className="font-medium">{child.name}</p>
                        <p className="text-xs text-muted-foreground">
                          child of {parent.name} · {child.is_active ? "active" : "inactive"}
                        </p>
                      </div>
                      <div className="flex gap-2">
                        <Can permission="categories.edit">
                          <Button size="sm" variant="secondary" onClick={() => startEdit({ ...child, parent_id: parent.id })}>
                            Edit
                          </Button>
                        </Can>
                        <Can permission="categories.delete">
                          <Button size="sm" variant="danger" onClick={() => remove(child.id)}>
                            Delete
                          </Button>
                        </Can>
                      </div>
                    </li>
                  ))}
                </ul>
              ) : null}
            </li>
          ))}
          {!tree.length ? (
            <li className="px-4 py-8 text-center text-sm text-muted-foreground">No categories yet.</li>
          ) : null}
        </ul>
      )}
    </div>
  );
}
