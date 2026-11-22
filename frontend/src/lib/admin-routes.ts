/** Map admin paths to the permission required to view them. */
export function requiredPermissionForPath(pathname: string): string | null {
  if (pathname === "/admin" || pathname === "/admin/") return "reports.view";
  if (pathname.startsWith("/admin/products/new")) return "products.create";
  if (pathname.match(/^\/admin\/products\/\d+/)) return "products.edit";
  if (pathname.startsWith("/admin/products")) return "products.view";
  if (pathname.startsWith("/admin/categories")) return "categories.view";
  if (pathname.startsWith("/admin/orders")) return "orders.view";
  if (pathname.startsWith("/admin/coupons")) return "coupons.manage";
  if (pathname.startsWith("/admin/customers")) return "customers.view";
  if (pathname.startsWith("/admin/roles")) return "roles.manage";
  if (pathname.startsWith("/admin/profile")) return null;
  return null;
}

export function firstAllowedAdminPath(permissions: string[] | undefined): string {
  const prefs: { path: string; permission: string }[] = [
    { path: "/admin", permission: "reports.view" },
    { path: "/admin/products", permission: "products.view" },
    { path: "/admin/orders", permission: "orders.view" },
    { path: "/admin/customers", permission: "customers.view" },
    { path: "/admin/categories", permission: "categories.view" },
    { path: "/admin/coupons", permission: "coupons.manage" },
    { path: "/admin/roles", permission: "roles.manage" },
  ];
  const set = new Set(permissions ?? []);
  return prefs.find((p) => set.has(p.permission))?.path ?? "/admin/profile";
}
