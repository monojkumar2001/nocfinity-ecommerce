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
    const apiHost = new URL(apiBase).hostname;

    // Prefer the API host the frontend is configured to use.
    if (
      (parsed.hostname === "localhost" || parsed.hostname === "127.0.0.1") &&
      (apiHost === "localhost" || apiHost === "127.0.0.1")
    ) {
      parsed.hostname = apiHost;
      parsed.port = new URL(apiBase).port || parsed.port;
      return parsed.toString();
    }
  } catch {
    return url;
  }

  return url;
}
