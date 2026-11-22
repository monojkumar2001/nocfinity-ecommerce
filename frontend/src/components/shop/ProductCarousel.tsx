"use client";

import { useEffect, useRef, useState } from "react";
import type { Product } from "@/lib/types";
import { ProductCard } from "@/components/shop/ProductCard";

const AUTO_MS = 3500;

export function ProductCarousel({
  products,
  ariaLabel,
}: {
  products: Product[];
  ariaLabel: string;
}) {
  const scrollerRef = useRef<HTMLDivElement>(null);
  const [paused, setPaused] = useState(false);

  // Duplicate track so loop feels continuous.
  const track = products.length > 1 ? [...products, ...products] : products;

  function scrollNext() {
    const el = scrollerRef.current;
    if (!el || products.length < 2) return;

    const card = el.querySelector<HTMLElement>("[data-product-card]");
    const amount = (card?.offsetWidth ?? 220) + 20;
    const half = el.scrollWidth / 2;

    el.scrollBy({ left: amount, behavior: "smooth" });

    // After smooth scroll settles near the duplicated half, jump back without animation.
    window.setTimeout(() => {
      if (!scrollerRef.current) return;
      if (scrollerRef.current.scrollLeft >= half - 8) {
        scrollerRef.current.scrollTo({ left: scrollerRef.current.scrollLeft - half, behavior: "auto" });
      }
    }, 400);
  }

  useEffect(() => {
    if (paused || products.length < 2) return;

    const id = window.setInterval(() => {
      scrollNext();
    }, AUTO_MS);

    return () => window.clearInterval(id);
  }, [paused, products.length]);

  if (!products.length) return null;

  return (
    <div
      className="relative mt-8 sm:mt-10"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocusCapture={() => setPaused(true)}
      onBlurCapture={() => setPaused(false)}
    >
      <div
        ref={scrollerRef}
        className="flex gap-4 overflow-x-auto scroll-smooth pb-2 scrollbar-none sm:gap-5"
        style={{ scrollbarWidth: "none" }}
        aria-label={ariaLabel}
      >
        {track.map((p, index) => (
          <div
            key={`${p.id}-${index}`}
            data-product-card
            className="w-[min(70vw,260px)] shrink-0 sm:w-[calc((100%-3.75rem)/4)]"
          >
            <ProductCard product={p} />
          </div>
        ))}
      </div>
    </div>
  );
}
