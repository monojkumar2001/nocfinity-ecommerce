"use client";

import Link from "next/link";
import Image from "next/image";
import { useState } from "react";
import { Mail } from "lucide-react";
import { homeLogo } from "@/lib/figma-home";

const linkColumns = [
  {
    title: "Company",
    links: [
      { href: "/products", label: "About" },
      { href: "/products", label: "Features" },
      { href: "/products", label: "Works" },
      { href: "/products", label: "Career" },
    ],
  },
  {
    title: "Help",
    links: [
      { href: "/orders", label: "Customer Support" },
      { href: "/orders", label: "Delivery Details" },
      { href: "/products", label: "Terms & Conditions" },
      { href: "/products", label: "Privacy Policy" },
    ],
  },
  {
    title: "FAQ",
    links: [
      { href: "/orders", label: "Account" },
      { href: "/cart", label: "Manage Deliveries" },
      { href: "/orders", label: "Orders" },
      { href: "/products", label: "Payments" },
    ],
  },
  {
    title: "Resources",
    links: [
      { href: "/products", label: "Free eBooks" },
      { href: "/products", label: "Development Tutorial" },
      { href: "/products", label: "How to - Blog" },
      { href: "/products", label: "Youtube Playlist" },
    ],
  },
];

function SocialIcon({
  label,
  filled,
  children,
}: {
  label: string;
  filled?: boolean;
  children: React.ReactNode;
}) {
  return (
    <a
      href="#"
      aria-label={label}
      className={`flex h-7 w-7 items-center justify-center rounded-full border border-black/20 transition hover:opacity-80 ${
        filled ? "bg-black text-white" : "bg-white text-black"
      }`}
    >
      {children}
    </a>
  );
}

function PaymentBadge({ children }: { children: React.ReactNode }) {
  return (
    <span className="inline-flex h-[30px] min-w-[46px] items-center justify-center rounded-[5px] border border-black/10 bg-white px-2">
      {children}
    </span>
  );
}

export function Footer() {
  const [email, setEmail] = useState("");
  const [done, setDone] = useState(false);

  return (
    <footer className="relative mt-auto bg-surface">
      {/* Newsletter — overlaps footer like Figma */}
      <div className="container-shop relative z-10 -mb-[90px] sm:-mb-[74px]">
        <div className="flex flex-col justify-between gap-8 rounded-[20px] bg-black px-6 py-8 text-white sm:flex-row sm:items-center sm:gap-16 sm:px-16 sm:py-11">
          <h2 className="font-display max-w-[551px] text-[32px] font-bold uppercase leading-[1.1] sm:text-[40px]">
            Stay upto date about our latest offers
          </h2>
          <form
            className="flex w-full max-w-[349px] shrink-0 flex-col gap-3.5"
            onSubmit={(e) => {
              e.preventDefault();
              setDone(true);
            }}
          >
            <div className="relative">
              <Mail className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-black/40" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your email address"
                className="h-12 w-full rounded-full bg-white py-3 pl-12 pr-4 text-sm font-normal text-black outline-none placeholder:text-black/40"
              />
            </div>
            <button
              type="submit"
              className="h-12 w-full rounded-full bg-white text-sm font-normal text-black transition hover:bg-white/90"
            >
              {done ? "Subscribed!" : "Subscribe to Newsletter"}
            </button>
          </form>
        </div>
      </div>

      <div className="bg-surface pb-8 pt-[140px] sm:pt-[130px]">
        <div className="container-shop grid gap-8 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(4,1fr)] lg:gap-6">
          <div className="max-w-[248px]">
            <Link href="/" className="inline-block" aria-label="Home">
              <Image
                src={homeLogo}
                alt="AURA"
                width={159}
                height={24}
                className="h-[23px] w-auto invert"
              />
            </Link>
            <p className="mt-4 text-sm leading-[22px] text-black/60">
              We have clothes that suits your style and which you&apos;re proud to wear. From women to
              men.
            </p>
            <div className="mt-6 flex gap-3">
              <SocialIcon label="Twitter">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
              </SocialIcon>
              <SocialIcon label="Facebook" filled>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                  <path d="M22 12a10 10 0 1 0-11.5 9.9v-7H8v-3h2.5V9.5c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.5V12H17l-.4 3h-2.7v7A10 10 0 0 0 22 12" />
                </svg>
              </SocialIcon>
              <SocialIcon label="Instagram">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
                  <rect x="3" y="3" width="18" height="18" rx="5" />
                  <circle cx="12" cy="12" r="4" />
                  <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                </svg>
              </SocialIcon>
              <SocialIcon label="GitHub">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                  <path d="M12 2C6.48 2 2 6.58 2 12.26c0 4.52 2.87 8.35 6.84 9.7.5.1.68-.22.68-.48 0-.24-.01-.87-.01-1.7-2.78.62-3.37-1.37-3.37-1.37-.45-1.18-1.11-1.5-1.11-1.5-.91-.64.07-.63.07-.63 1 .07 1.53 1.06 1.53 1.06.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.07 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.7 0 0 .84-.27 2.75 1.05A9.3 9.3 0 0 1 12 6.8c.85 0 1.7.12 2.5.34 1.9-1.32 2.74-1.05 2.74-1.05.55 1.4.2 2.44.1 2.7.64.72 1.03 1.63 1.03 2.75 0 3.94-2.34 4.8-4.57 5.06.36.32.68.94.68 1.9 0 1.37-.01 2.47-.01 2.8 0 .27.18.59.69.48A10.05 10.05 0 0 0 22 12.26C22 6.58 17.52 2 12 2z" />
                </svg>
              </SocialIcon>
            </div>
          </div>

          {linkColumns.map((col) => (
            <div key={col.title}>
              <p className="text-sm font-normal uppercase tracking-[3px] text-black">{col.title}</p>
              <ul className="mt-4 space-y-3.5 text-base font-normal text-black/60">
                {col.links.map((l) => (
                  <li key={l.label}>
                    <Link href={l.href} className="transition hover:text-black">
                      {l.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="container-shop mt-12 flex flex-col items-start justify-between gap-4 border-t border-black/10 pt-5 sm:flex-row sm:items-center">
          <p className="text-sm font-normal text-black/60">
            Shop.co © 2000-2023, All Rights Reserved
          </p>
          <div className="flex flex-wrap items-center gap-3">
            <PaymentBadge>
              <span className="text-[11px] font-bold italic tracking-tight text-[#1A1F71]">Visa</span>
            </PaymentBadge>
            <PaymentBadge>
              <span className="flex items-center gap-0.5">
                <span className="h-3.5 w-3.5 rounded-full bg-[#EB001B]" />
                <span className="-ml-1.5 h-3.5 w-3.5 rounded-full bg-[#F79E1B] opacity-90" />
              </span>
            </PaymentBadge>
            <PaymentBadge>
              <span className="text-[10px] font-bold text-[#003087]">PayPal</span>
            </PaymentBadge>
            <PaymentBadge>
              <span className="text-[10px] font-semibold tracking-tight"> Pay</span>
            </PaymentBadge>
            <PaymentBadge>
              <span className="text-[10px] font-medium tracking-tight">G Pay</span>
            </PaymentBadge>
          </div>
        </div>
      </div>
    </footer>
  );
}
