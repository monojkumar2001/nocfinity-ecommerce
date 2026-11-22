"use client";

import { getUser, hasPermission } from "@/lib/auth";
import type { ReactNode } from "react";

type CanProps = {
  permission: string | string[];
  children: ReactNode;
  fallback?: ReactNode;
};

/** Renders children only when the signed-in staff user has the permission(s). */
export function Can({ permission, children, fallback = null }: CanProps) {
  const user = getUser();
  const needed = Array.isArray(permission) ? permission : [permission];
  const allowed = needed.every((p) => hasPermission(user, p));
  return allowed ? <>{children}</> : <>{fallback}</>;
}
