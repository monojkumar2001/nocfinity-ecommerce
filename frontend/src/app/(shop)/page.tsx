"use client";

import Link from "next/link";
import Image from "next/image";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/Button";
import { ProductCarousel } from "@/components/shop/ProductCarousel";
import { TestimonialsCarousel } from "@/components/shop/TestimonialsCarousel";
import { Spinner } from "@/components/ui/Spinner";
import { apiRequest, buildQuery } from "@/lib/api";
import type { Product } from "@/lib/types";
import { homeBrands, homeHero, homeStyles } from "@/lib/figma-home";

export default function HomePage() {
  const [newArrivals, setNewArrivals] = useState<Product[]>([]);
  const [topSelling, setTopSelling] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      apiRequest<Product[]>(`/products${buildQuery({ per_page: 12, sort: "newest" })}`),
      apiRequest<Product[]>(`/products${buildQuery({ per_page: 12, sort: "price_desc" })}`),
    ])
      .then(([newest, priced]) => {
        setNewArrivals(newest.data);
        setTopSelling(priced.data);
      })
      .catch(() => undefined)
      .finally(() => setLoading(false));
  }, []);

  return (
    <>
      {/* Hero — image flush against brand bar (Figma: no gap) */}
      <section className="bg-hero leading-[0]">
        <div className="container-shop grid items-end gap-0 lg:grid-cols-2">
          <div className="relative z-10 flex flex-col justify-center py-10 leading-normal sm:py-14 lg:min-h-[663px] lg:py-16 lg:pr-8">
            <h1 className="font-display max-w-[577px] text-[36px] font-bold uppercase leading-[1] tracking-tight sm:text-[48px] lg:text-[64px]">
              {homeHero.title}
            </h1>
            <p className="mt-5 max-w-[545px] text-sm leading-[22px] text-black/60 sm:text-base">
              {homeHero.subtitle}
            </p>
            <Link href="/products" className="mt-6 inline-block sm:mt-8">
              <Button size="lg" className="w-full min-w-[210px] sm:w-auto">
                Shop Now
              </Button>
            </Link>

            <div className="mt-8 flex flex-wrap leading-normal sm:mt-12 sm:flex-nowrap">
              {homeHero.stats.map((stat, i) => (
                <div
                  key={stat.label}
                  className={`w-1/2 py-1 sm:w-auto sm:py-0 ${
                    i > 0 ? "sm:ml-8 sm:border-l sm:border-black/10 sm:pl-8" : ""
                  } ${i === 2 ? "mt-3 w-full sm:mt-0 sm:w-auto" : ""}`}
                >
                  <p className="text-2xl font-bold leading-none sm:text-[40px]">{stat.value}</p>
                  <p className="mt-1 text-xs leading-normal text-black/60 sm:text-base">{stat.label}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="relative ml-auto block h-[420px] w-full max-w-[638px] sm:h-[520px] lg:h-[663px]">
            <Image
              src={homeHero.desktopImage}
              alt="Find clothes that matches your style"
              width={638}
              height={663}
              priority
              className="h-full w-full object-cover object-top lg:object-contain lg:object-bottom"
              sizes="(max-width: 1024px) 100vw, 638px"
            />
            <Image
              src={homeHero.vector}
              alt=""
              width={104}
              height={104}
              className="hero-sparkle hero-sparkle--lg right-[6%] top-[8%] h-12 w-12 sm:h-16 sm:w-16 lg:h-[104px] lg:w-[104px]"
              aria-hidden
            />
            <Image
              src={homeHero.vector}
              alt=""
              width={56}
              height={56}
              className="hero-sparkle hero-sparkle--sm left-[4%] top-[42%] h-8 w-8 sm:h-10 sm:w-10 lg:h-14 lg:w-14"
              aria-hidden
            />
          </div>
        </div>
      </section>

      <div className="m-0 bg-black py-7 sm:py-11">
        <div className="container-shop flex flex-wrap items-center justify-center gap-x-8 gap-y-5 sm:justify-between">
          {homeBrands.map((brand) => (
            <Image
              key={brand.name}
              src={brand.src}
              alt={brand.name}
              width={166}
              height={33}
              className="h-6 w-auto brightness-0 invert sm:h-8"
            />
          ))}
        </div>
      </div>

      <section className="container-shop relative py-12 sm:py-16">
        <h2 className="section-title">New Arrivals</h2>
        {loading ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : (
          <ProductCarousel products={newArrivals} ariaLabel="New Arrivals" />
        )}
        <div className="mt-8 flex justify-center sm:mt-9">
          <Link href="/products?sort=newest">
            <Button variant="outline" size="lg" className="min-w-[218px] border-black/10">
              View All
            </Button>
          </Link>
        </div>
      </section>

      <div className="container-shop">
        <hr className="border-black/10" />
      </div>

      <section className="container-shop relative py-12 sm:py-16">
        <h2 className="section-title">Top Selling</h2>
        {loading ? (
          <div className="flex justify-center py-16">
            <Spinner className="h-8 w-8" />
          </div>
        ) : (
          <ProductCarousel products={topSelling} ariaLabel="Top Selling" />
        )}
        <div className="mt-8 flex justify-center sm:mt-9">
          <Link href="/products?sort=price_desc">
            <Button variant="outline" size="lg" className="min-w-[218px] border-black/10">
              View All
            </Button>
          </Link>
        </div>
      </section>

      <section className="container-shop pb-12 sm:pb-16">
        <div className="rounded-[20px] bg-surface px-6 py-8 sm:rounded-[40px] sm:px-10 sm:py-12 lg:px-16 lg:py-16">
          <h2 className="section-title">Browse by dress style</h2>
          <div className="mt-8 grid grid-cols-1 gap-4 sm:mt-10 sm:grid-cols-12 sm:gap-5">
            {homeStyles.map((style) => (
              <Link
                key={style.name}
                href={style.href}
                className={`relative h-[190px] overflow-hidden rounded-[20px] bg-white sm:h-[289px] ${
                  style.span === "wide" ? "sm:col-span-7" : "sm:col-span-5"
                }`}
              >
                <Image
                  src={style.image}
                  alt={style.name}
                  fill
                  className="object-cover object-right"
                  sizes="(max-width: 640px) 100vw, 60vw"
                />
                <span className="absolute left-6 top-4 z-10 text-2xl font-normal text-black sm:left-9 sm:top-6 sm:text-4xl">
                  {style.name}
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <TestimonialsCarousel />
    </>
  );
}
