import { apiRequest, AUTH_TOKEN_KEY } from "./api";
import type { User } from "./types";

const USER_KEY = "nf_user";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(AUTH_TOKEN_KEY);
}

export function getUser(): User | null {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem(USER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as User;
  } catch {
    return null;
  }
}

export function setAuth(token: string, user: User) {
  localStorage.setItem(AUTH_TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearAuth() {
  localStorage.removeItem(AUTH_TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

/** Staff = flagged by API or holding any role / permission. */
export function isStaff(user: User | null): boolean {
  if (!user) return false;
  if (user.is_staff) return true;
  return (user.roles?.length ?? 0) > 0 || (user.permissions?.length ?? 0) > 0;
}

export function hasPermission(user: User | null, permission: string): boolean {
  if (!user?.permissions) return false;
  return user.permissions.includes(permission);
}

export function hasAnyPermission(user: User | null, permissions: string[]): boolean {
  return permissions.some((p) => hasPermission(user, p));
}

type AuthPayload = {
  user: User;
  token: string;
  token_type: string;
};

export async function registerCustomer(data: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}) {
  const { data: payload } = await apiRequest<AuthPayload>("/register", {
    method: "POST",
    body: JSON.stringify(data),
  });
  setAuth(payload.token, payload.user);
  return payload;
}

export async function loginCustomer(data: { email: string; password: string }) {
  const { data: payload } = await apiRequest<AuthPayload>("/login", {
    method: "POST",
    body: JSON.stringify(data),
  });
  setAuth(payload.token, payload.user);
  return payload;
}

export async function loginStaff(data: { email: string; password: string }) {
  const { data: payload } = await apiRequest<AuthPayload>("/admin/login", {
    method: "POST",
    body: JSON.stringify(data),
  });
  setAuth(payload.token, payload.user);
  return payload;
}

export async function fetchCurrentUser() {
  const { data } = await apiRequest<{ user: User }>("/user");
  if (typeof window !== "undefined") {
    localStorage.setItem(USER_KEY, JSON.stringify(data.user));
  }
  return data.user;
}

export async function logout() {
  try {
    if (getToken()) {
      await apiRequest<null>("/logout", { method: "POST" });
    }
  } finally {
    clearAuth();
  }
}

export async function forgotPassword(email: string) {
  return apiRequest<null>("/forgot-password", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

export async function resetPassword(data: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}) {
  return apiRequest<null>("/reset-password", {
    method: "POST",
    body: JSON.stringify(data),
  });
}