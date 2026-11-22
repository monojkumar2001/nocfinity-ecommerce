import type { NextConfig } from "next";

const securityHeaders = [
  { key: "X-Frame-Options", value: "DENY" },
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
  { key: "X-XSS-Protection", value: "0" },
  { key: "Cross-Origin-Opener-Policy", value: "same-origin" },
  {
    key: "Content-Security-Policy",
    value: [
      "default-src 'self'",
      "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com",
      "style-src 'self' 'unsafe-inline'",
      "img-src 'self' data: blob: https: http://localhost:* http://127.0.0.1:*",
      "font-src 'self' data:",
      "connect-src 'self' http://localhost:* http://127.0.0.1:* https: https://api.stripe.com",
      "frame-src https://js.stripe.com https://hooks.stripe.com",
      "frame-ancestors 'none'",
      "base-uri 'self'",
      "form-action 'self'",
    ].join("; "),
  },
];

const apiOrigin = (process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1").replace(
  /\/api\/v1\/?$/,
  "",
);

const nextConfig: NextConfig = {
  output: "standalone",
  // Allow Cloudflare / Vercel preview hosts to hit the Next.js Dev Server.
  allowedDevOrigins: [
    "allows-dentists-lodge-deal.trycloudflare.com",
    "aquatic-apr-inter-ottawa.trycloudflare.com",
    "alan-realm-equity-mhz.trycloudflare.com",
    "*.trycloudflare.com",
    "localhost",
    "127.0.0.1",
  ],
  cacheComponents: true,
  partialPrefetching: true,
  async rewrites() {
    return [
      {
        source: "/backend-api/:path*",
        destination: `${apiOrigin}/api/v1/:path*`,
      },
    ];
  },
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "picsum.photos", pathname: "/**" },
      { protocol: "https", hostname: "images.unsplash.com", pathname: "/**" },
      { protocol: "https", hostname: "*.up.railway.app", pathname: "/**" },
      { protocol: "https", hostname: "*.onrender.com", pathname: "/**" },
      { protocol: "http", hostname: "127.0.0.1", port: "8000", pathname: "/**" },
      { protocol: "http", hostname: "localhost", port: "8000", pathname: "/**" },
      { protocol: "http", hostname: "127.0.0.1", pathname: "/**" },
      { protocol: "http", hostname: "localhost", pathname: "/**" },
    ],
  },
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
  async headers() {
    return [
      {
        source: "/:path*",
        headers: securityHeaders,
      },
    ];
  },
};

export default nextConfig;