"use client";

import { useEffect, useRef, useState } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { StarRating } from "@/components/shop/StarRating";
import { homeTestimonials } from "@/lib/figma-home";

const AUTO_MS = 3500;

export function TestimonialsCarousel() {
  const scrollerRef = useRef<HTMLDivElement>(null);
  const [paused, setPaused] = useState(false);

  function scrollByCard(dir: -1 | 1) {
    const el = scrollerRef.current;
    if (!el) return;
    const card = el.querySelector<HTMLElement>("[data-testimonial-card]");
    const amount = (card?.offsetWidth ?? 400) + 20;
    const maxScroll = el.scrollWidth - el.clientWidth;
    const next = el.scrollLeft + dir * amount;

    if (dir === 1 && next >= maxScroll - 4) {
      el.scrollTo({ left: 0, behavior: "smooth" });
      return;
    }
    if (dir === -1 && next <= 4) {
      el.scrollTo({ left: maxScroll, behavior: "smooth" });
      return;
    }

    el.scrollBy({ left: dir * amount, behavior: "smooth" });
  }

  useEffect(() => {
    if (paused) return;

    const id = window.setInterval(() => {
      scrollByCard(1);
    }, AUTO_MS);

    return () => window.clearInterval(id);
  }, [paused]);

  return (
    <section className="overflow-hidden pb-16 sm:pb-20">
      <div className="container-shop flex items-end justify-between gap-4">
        <h2 className="font-display max-w-[654px] text-left text-[32px] font-bold uppercase leading-[1] sm:text-5xl">
          Our happy customers
        </h2>
        <div className="mb-1 flex shrink-0 gap-3">
          <button
            type="button"
            aria-label="Previous testimonials"
            onClick={() => scrollByCard(-1)}
            className="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 bg-white transition hover:bg-black/5"
          >
            <ArrowLeft className="h-5 w-5" />
          </button>
          <button
            type="button"
            aria-label="Next testimonials"
            onClick={() => scrollByCard(1)}
            className="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 bg-white transition hover:bg-black/5"
          >
            <ArrowRight className="h-5 w-5" />
          </button>
        </div>
      </div>

      <div
        className="relative mt-8 sm:mt-10"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
        onFocusCapture={() => setPaused(true)}
        onBlurCapture={() => setPaused(false)}
      >
        <div className="pointer-events-none absolute inset-y-0 left-0 z-10 hidden w-8 bg-gradient-to-r from-white to-transparent sm:block lg:w-16" />
        <div className="pointer-events-none absolute inset-y-0 right-0 z-10 hidden w-8 bg-gradient-to-l from-white to-transparent sm:block lg:w-16" />

        <div
          ref={scrollerRef}
          className="flex gap-5 overflow-x-auto scroll-smooth px-4 pb-2 scrollbar-none sm:px-6 lg:px-[max(1.5rem,calc((100vw-77.5rem)/2+1.5rem))]"
          style={{ scrollbarWidth: "none" }}
        >
          {homeTestimonials.map((t) => (
            <article
              key={t.name}
              data-testimonial-card
              className="w-[min(100%,358px)] shrink-0 rounded-[20px] border border-black/10 bg-white px-6 py-6 sm:w-[400px] sm:px-8 sm:py-7"
            >
              <StarRating rating={5} />
              <p className="mt-4 flex items-center gap-1 text-lg font-normal text-black sm:text-xl">
                {t.name}
                <span
                  className="inline-flex h-[19px] w-[19px] items-center justify-center rounded-full bg-[#01AB31] text-[10px] leading-none text-white"
                  aria-label="Verified"
                >
                  ✓
                </span>
              </p>
              <p className="mt-3 text-sm leading-[22px] text-black/60 sm:text-base sm:leading-6">
                &ldquo;{t.text}&rdquo;
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
