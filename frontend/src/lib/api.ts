import type { PaginationMeta } from "./types";

function resolveApiUrl(): string {
  // Always same-origin in the browser (Next rewrite → Laravel).
  if (typeof window !== "undefined") {
    return "/backend-api";
  }
  return process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";
}

export const CART_TOKEN_KEY = "nf_cart_token";
export const AUTH_TOKEN_KEY = "nf_token";

export type ApiEnvelope<T> = {
  success: boolean;
  message: string;
  data?: T;
  meta?: PaginationMeta;
  errors?: Record<string, string[]>;
};

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(
    message: string,
    status: number,
    errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
  }
}

function persistCartToken(response: Response) {
  if (typeof window === "undefined") return;
  const token = response.headers.get("X-Cart-Token");
  if (token) {
    localStorage.setItem(CART_TOKEN_KEY, token);
  }
}

function buildHeaders(init?: HeadersInit): Headers {
  const headers = new Headers(init);
  if (!headers.has("Accept")) {
    headers.set("Accept", "application/json");
  }

  if (typeof window !== "undefined") {
    const auth = localStorage.getItem(AUTH_TOKEN_KEY);
    if (auth) {
      headers.set("Authorization", `Bearer ${auth}`);
    }
    const cart = localStorage.getItem(CART_TOKEN_KEY);
    if (cart) {
      headers.set("X-Cart-Token", cart);
    }
  }

  return headers;
}

function parseErrorMessage(body: ApiEnvelope<unknown>, status: number): string {
  if (body.errors) {
    const flat = Object.values(body.errors).flat();
    if (flat.length) return flat.join(" ");
  }
  return body.message || `Request failed (${status})`;
}

export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {},
): Promise<{ data: T; meta?: PaginationMeta; message?: string }> {
  const isFormData =
    typeof FormData !== "undefined" && options.body instanceof FormData;

  const headers = buildHeaders(options.headers);
  if (!isFormData && options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  if (isFormData) {
    headers.delete("Content-Type");
  }

  const response = await fetch(`${resolveApiUrl()}${endpoint}`, {
    ...options,
    headers,
  });

  persistCartToken(response);

  let body: ApiEnvelope<T>;
  const raw = await response.text();
  try {
    body = raw
      ? (JSON.parse(raw) as ApiEnvelope<T>)
      : { success: false, message: "Empty response" };
  } catch {
    throw new ApiError("Invalid server response", response.status);
  }

  if (!response.ok || !body.success) {
    throw new ApiError(parseErrorMessage(body, response.status), response.status, body.errors);
  }

  return { data: body.data as T, meta: body.meta, message: body.message };
}

export function buildQuery(params: Record<string, string | number | undefined | null>) {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      search.set(key, String(value));
    }
  });
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}