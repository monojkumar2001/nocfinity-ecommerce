/**
 * Normalize Laravel / CDN media URLs for the Next.js storefront.
 * Handles relative `/storage/...` paths and localhost ↔ 127.0.0.1 mismatches.
 */
export function resolveMediaUrl(src?: string | null): string | null {
  if (!src) return null;

  const trimmed = src.trim();
  if (!trimmed) return null;

  const apiBase = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1").replace(
    /\/api\/v1\/?$/,
    "",
  );

  let url = trimmed;

  if (url.startsWith("/")) {
    url = `${apiBase}${url}`;
  }

  try {
    const parsed = new URL(url);
    const api = new URL(apiBase);

    // Rewrite local Laravel media hosts to the configured API origin (tunnels/prod).
    if (parsed.hostname === "localhost" || parsed.hostname === "127.0.0.1") {
      parsed.protocol = api.protocol;
      parsed.hostname = api.hostname;
      parsed.port = api.port;
      return parsed.toString();
    }
  } catch {
    return url;
  }

  return url;
}
