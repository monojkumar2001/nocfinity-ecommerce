import type { Metadata } from "next";
import { Inter, Oswald } from "next/font/google";
import "./globals.css";

/* Satoshi ≈ Inter; Integral CF ≈ Oswald Black for SHOP.CO headings */
const satoshi = Inter({
  variable: "--font-satoshi",
  subsets: ["latin"],
});

const integral = Oswald({
  variable: "--font-integral",
  subsets: ["latin"],
  weight: ["500", "600", "700"],
});

const storeName = process.env.NEXT_PUBLIC_STORE_NAME ?? "Nocfinity";

export const metadata: Metadata = {
  title: {
    default: storeName,
    template: `%s · ${storeName}`,
  },
  description: "Find gadgets that match your style — Nocfinity electronics storefront.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html
      lang="en"
      className={`${satoshi.variable} ${integral.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <body className="min-h-full flex flex-col bg-background text-foreground" suppressHydrationWarning>
        {children}
      </body>
    </html>
  );
}
