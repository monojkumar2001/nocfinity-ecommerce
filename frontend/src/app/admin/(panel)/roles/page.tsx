"use client";

import { useEffect, useMemo, useState } from "react";
import { apiRequest } from "@/lib/api";
import type { RoleRecord, StaffMember } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { cn } from "@/lib/cn";

type RolesPayload = {
  roles: RoleRecord[];
  permissions: string[];
};

type StaffPayload = {
  staff: StaffMember[];
  roles: { id: number; name: string; is_super?: boolean }[];
};

type Tab = "matrix" | "staff";

function groupPermissions(permissions: string[]) {
  const groups = new Map<string, string[]>();
  for (const perm of permissions) {
    const [resource] = perm.split(".");
    const key = resource || "other";
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key)!.push(perm);
  }
  return Array.from(groups.entries()).sort(([a], [b]) => a.localeCompare(b));
}

export default function AdminRolesPage() {
  const [tab, setTab] = useState<Tab>("matrix");
  const [data, setData] = useState<RolesPayload | null>(null);
  const [staffData, setStaffData] = useState<StaffPayload | null>(null);
  const [selectedRole, setSelectedRole] = useState<RoleRecord | null>(null);
  const [roleName, setRoleName] = useState("");
  const [checked, setChecked] = useState<Set<string>>(new Set());
  const [newRoleName, setNewRoleName] = useState("");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [staffForm, setStaffForm] = useState({
    name: "",
    email: "",
    password: "",
    roles: [] as string[],
  });
  const [editingStaff, setEditingStaff] = useState<StaffMember | null>(null);
  const [staffRoles, setStaffRoles] = useState<Set<string>>(new Set());

  const permissionGroups = useMemo(
    () => (data ? groupPermissions(data.permissions) : []),
    [data],
  );

  const assignableRoles = useMemo(
    () => (staffData?.roles ?? data?.roles ?? []).filter((r) => !r.is_super && r.name !== "Super Admin"),
    [staffData, data],
  );

  async function loadRoles(preferRoleId?: number) {
    const { data: d } = await apiRequest<RolesPayload>("/admin/roles");
    setData(d);
    const next =
      d.roles.find((r) => r.id === preferRoleId) ??
      d.roles.find((r) => r.id === selectedRole?.id) ??
      d.roles.find((r) => !r.is_super) ??
      d.roles[0] ??
      null;
    setSelectedRole(next);
    if (next) {
      setRoleName(next.name);
      setChecked(new Set(next.permissions));
    }
  }

  async function loadStaff() {
    const { data: d } = await apiRequest<StaffPayload>("/admin/staff");
    setStaffData(d);
  }

  useEffect(() => {
    loadRoles().catch((err) => setError(err instanceof Error ? err.message : "Failed to load roles"));
    loadStaff().catch(() => undefined);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!selectedRole) return;
    setRoleName(selectedRole.name);
    setChecked(new Set(selectedRole.permissions));
  }, [selectedRole]);

  function togglePermission(name: string) {
    if (selectedRole?.is_super) return;
    setChecked((prev) => {
      const next = new Set(prev);
      if (next.has(name)) next.delete(name);
      else next.add(name);
      return next;
    });
  }

  function toggleGroup(perms: string[], enable: boolean) {
    if (selectedRole?.is_super) return;
    setChecked((prev) => {
      const next = new Set(prev);
      for (const p of perms) {
        if (enable) next.add(p);
        else next.delete(p);
      }
      return next;
    });
  }

  async function createRole(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setMessage(null);
    setSaving(true);
    try {
      const { data: created } = await apiRequest<RoleRecord>("/admin/roles", {
        method: "POST",
        body: JSON.stringify({ name: newRoleName, permissions: [] }),
      });
      setNewRoleName("");
      setMessage(`Role “${created.name}” created.`);
      await loadRoles(created.id);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not create role");
    } finally {
      setSaving(false);
    }
  }

  async function savePermissions() {
    if (!selectedRole || selectedRole.is_super) return;
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      await apiRequest(`/admin/roles/${selectedRole.id}`, {
        method: "PUT",
        body: JSON.stringify({
          name: roleName.trim() || selectedRole.name,
          permissions: Array.from(checked),
        }),
      });
      setMessage("Permissions saved.");
      await loadRoles(selectedRole.id);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Save failed");
    } finally {
      setSaving(false);
    }
  }

  async function deleteRole() {
    if (!selectedRole || selectedRole.is_super) return;
    if (!confirm(`Delete role “${selectedRole.name}”?`)) return;
    setSaving(true);
    setError(null);
    try {
      await apiRequest(`/admin/roles/${selectedRole.id}`, { method: "DELETE" });
      setSelectedRole(null);
      setMessage("Role deleted.");
      await loadRoles();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Delete failed");
    } finally {
      setSaving(false);
    }
  }

  async function createStaff(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      await apiRequest("/admin/staff", {
        method: "POST",
        body: JSON.stringify(staffForm),
      });
      setStaffForm({ name: "", email: "", password: "", roles: [] });
      setMessage("Staff member created.");
      await loadStaff();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not create staff");
    } finally {
      setSaving(false);
    }
  }

  function startEditStaff(member: StaffMember) {
    if (member.is_super) return;
    setEditingStaff(member);
    setStaffRoles(new Set(member.roles));
  }

  async function saveStaffRoles() {
    if (!editingStaff || editingStaff.is_super) return;
    setSaving(true);
    setError(null);
    try {
      await apiRequest(`/admin/staff/${editingStaff.id}/roles`, {
        method: "PUT",
        body: JSON.stringify({ roles: Array.from(staffRoles) }),
      });
      setEditingStaff(null);
      setMessage("Staff roles updated.");
      await loadStaff();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Update failed");
    } finally {
      setSaving(false);
    }
  }

  if (!data) {
    return error ? <p className="text-red-600">{error}</p> : <Spinner className="mt-12" />;
  }

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold">Roles & permissions</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Create custom roles, assign granular permissions, and manage staff access.
          </p>
        </div>
        <div className="flex rounded-lg border border-border bg-white p-1 text-sm">
          <button
            type="button"
            className={cn("rounded-md px-3 py-1.5", tab === "matrix" ? "bg-accent-soft text-accent" : "text-muted-foreground")}
            onClick={() => setTab("matrix")}
          >
            Permission matrix
          </button>
          <button
            type="button"
            className={cn("rounded-md px-3 py-1.5", tab === "staff" ? "bg-accent-soft text-accent" : "text-muted-foreground")}
            onClick={() => {
              setTab("staff");
              void loadStaff();
            }}
          >
            Staff roles
          </button>
        </div>
      </div>

      {message ? <p className="mt-4 text-sm text-green-700">{message}</p> : null}
      {error ? <p className="mt-4 text-sm text-red-600">{error}</p> : null}

      {tab === "matrix" ? (
        <>
          <form onSubmit={createRole} className="mt-6 flex max-w-lg gap-2">
            <Input
              label="New custom role"
              value={newRoleName}
              onChange={(e) => setNewRoleName(e.target.value)}
              required
              className="flex-1"
              placeholder="e.g. Inventory Lead"
            />
            <div className="flex items-end">
              <Button type="submit" disabled={saving}>
                Add role
              </Button>
            </div>
          </form>

          <div className="mt-8 grid gap-6 lg:grid-cols-[240px_1fr]">
            <ul className="space-y-1 rounded-xl border border-border bg-white p-2 text-sm">
              {data.roles.map((role) => (
                <li key={role.id}>
                  <button
                    type="button"
                    onClick={() => setSelectedRole(role)}
                    className={cn(
                      "w-full rounded-lg px-3 py-2 text-left",
                      selectedRole?.id === role.id ? "bg-accent-soft text-accent" : "hover:bg-surface",
                    )}
                  >
                    <span className="font-medium">{role.name}</span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">
                      {role.is_super ? "All permissions" : `${role.permissions.length} permissions`}
                      {typeof role.users_count === "number" ? ` · ${role.users_count} staff` : ""}
                    </span>
                  </button>
                </li>
              ))}
            </ul>

            {selectedRole ? (
              <div className="rounded-xl border border-border bg-white p-6">
                {selectedRole.is_super ? (
                  <>
                    <h2 className="font-semibold">{selectedRole.name}</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                      Super Admin always has every permission and cannot be edited or deleted.
                    </p>
                    <div className="mt-4 overflow-x-auto">
                      <table className="w-full min-w-[480px] text-left text-sm">
                        <thead>
                          <tr className="border-b border-border text-muted-foreground">
                            <th className="py-2 pr-4 font-medium">Resource</th>
                            <th className="py-2 font-medium">Permissions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {permissionGroups.map(([group, perms]) => (
                            <tr key={group} className="border-b border-border/70">
                              <td className="py-2 pr-4 capitalize">{group}</td>
                              <td className="py-2 text-muted-foreground">{perms.join(", ")}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </>
                ) : (
                  <>
                    <div className="flex flex-wrap items-end justify-between gap-3">
                      <Input
                        label="Role name"
                        value={roleName}
                        onChange={(e) => setRoleName(e.target.value)}
                        className="max-w-xs"
                      />
                      <div className="flex gap-2">
                        <Button onClick={savePermissions} disabled={saving}>
                          Save matrix
                        </Button>
                        <Button variant="danger" onClick={deleteRole} disabled={saving}>
                          Delete
                        </Button>
                      </div>
                    </div>

                    <div className="mt-6 overflow-x-auto">
                      <table className="w-full min-w-[560px] border-collapse text-sm">
                        <thead>
                          <tr className="border-b border-border bg-surface text-left text-muted-foreground">
                            <th className="px-3 py-2 font-medium">Resource</th>
                            <th className="px-3 py-2 font-medium">Permissions</th>
                            <th className="px-3 py-2 font-medium">All</th>
                          </tr>
                        </thead>
                        <tbody>
                          {permissionGroups.map(([group, perms]) => {
                            const allOn = perms.every((p) => checked.has(p));
                            return (
                              <tr key={group} className="border-b border-border align-top">
                                <td className="px-3 py-3 font-medium capitalize">{group}</td>
                                <td className="px-3 py-3">
                                  <div className="grid gap-2 sm:grid-cols-2">
                                    {perms.map((perm) => (
                                      <label key={perm} className="flex items-center gap-2">
                                        <input
                                          type="checkbox"
                                          checked={checked.has(perm)}
                                          onChange={() => togglePermission(perm)}
                                        />
                                        <span className="font-mono text-xs">{perm}</span>
                                      </label>
                                    ))}
                                  </div>
                                </td>
                                <td className="px-3 py-3">
                                  <input
                                    type="checkbox"
                                    checked={allOn}
                                    onChange={(e) => toggleGroup(perms, e.target.checked)}
                                    aria-label={`Toggle all ${group}`}
                                  />
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  </>
                )}
              </div>
            ) : null}
          </div>
        </>
      ) : (
        <div className="mt-8 grid gap-8 lg:grid-cols-2">
          <form onSubmit={createStaff} className="space-y-3 rounded-xl border border-border bg-white p-5">
            <h2 className="font-semibold">Add staff member</h2>
            <Input
              label="Name"
              required
              value={staffForm.name}
              onChange={(e) => setStaffForm((f) => ({ ...f, name: e.target.value }))}
            />
            <Input
              label="Email"
              type="email"
              required
              value={staffForm.email}
              onChange={(e) => setStaffForm((f) => ({ ...f, email: e.target.value }))}
            />
            <Input
              label="Password"
              type="password"
              required
              value={staffForm.password}
              onChange={(e) => setStaffForm((f) => ({ ...f, password: e.target.value }))}
              placeholder="Min 8 · upper, lower, number, symbol"
            />
            <fieldset>
              <legend className="mb-2 text-sm font-medium">Roles</legend>
              <div className="space-y-2">
                {assignableRoles.map((role) => (
                  <label key={role.name} className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={staffForm.roles.includes(role.name)}
                      onChange={() =>
                        setStaffForm((f) => ({
                          ...f,
                          roles: f.roles.includes(role.name)
                            ? f.roles.filter((r) => r !== role.name)
                            : [...f.roles, role.name],
                        }))
                      }
                    />
                    {role.name}
                  </label>
                ))}
              </div>
            </fieldset>
            <Button type="submit" disabled={saving || staffForm.roles.length === 0}>
              Create staff
            </Button>
          </form>

          <div className="rounded-xl border border-border bg-white p-5">
            <h2 className="font-semibold">Current staff</h2>
            {!staffData ? (
              <Spinner className="mt-6" />
            ) : (
              <ul className="mt-4 divide-y divide-border text-sm">
                {staffData.staff.map((member) => (
                  <li key={member.id} className="flex flex-wrap items-start justify-between gap-3 py-3">
                    <div>
                      <p className="font-medium">{member.name}</p>
                      <p className="text-xs text-muted-foreground">{member.email}</p>
                      <p className="mt-1 text-xs">{member.roles.join(", ")}</p>
                    </div>
                    {member.is_super ? (
                      <span className="text-xs text-muted-foreground">Protected</span>
                    ) : (
                      <Button size="sm" variant="secondary" onClick={() => startEditStaff(member)}>
                        Edit roles
                      </Button>
                    )}
                  </li>
                ))}
              </ul>
            )}

            {editingStaff ? (
              <div className="mt-6 rounded-lg border border-border bg-surface p-4">
                <p className="text-sm font-medium">Roles for {editingStaff.name}</p>
                <div className="mt-3 space-y-2">
                  {assignableRoles.map((role) => (
                    <label key={role.name} className="flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={staffRoles.has(role.name)}
                        onChange={() =>
                          setStaffRoles((prev) => {
                            const next = new Set(prev);
                            if (next.has(role.name)) next.delete(role.name);
                            else next.add(role.name);
                            return next;
                          })
                        }
                      />
                      {role.name}
                    </label>
                  ))}
                </div>
                <div className="mt-4 flex gap-2">
                  <Button size="sm" onClick={saveStaffRoles} disabled={saving || staffRoles.size === 0}>
                    Save
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => setEditingStaff(null)}>
                    Cancel
                  </Button>
                </div>
              </div>
            ) : null}
          </div>
        </div>
      )}
    </div>
  );
}
