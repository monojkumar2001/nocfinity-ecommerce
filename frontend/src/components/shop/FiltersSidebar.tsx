"use client";

import { useEffect, useState } from "react";
import { ChevronDown, SlidersHorizontal, X } from "lucide-react";
import { cn } from "@/lib/cn";
import { apiRequest } from "@/lib/api";
import { DRESS_STYLES, PRODUCT_COLORS, PRODUCT_SIZES } from "@/lib/product-filters";

export type FiltersState = {
  category: string;
  min_price: string;
  max_price: string;
  color: string;
  size: string;
  dress_style: string;
};

type Props = {
  categories: { id: number; name: string; slug: string }[];
  filters: FiltersState;
  priceMax: number;
  onPriceMaxChange: (v: number) => void;
  onChange: (patch: Partial<FiltersState>) => void;
  onApply: () => void;
  onClose?: () => void;
  className?: string;
};

const FILTER_CATEGORY_SLUGS = ["t-shirts", "shorts", "shirts", "hoodie", "jeans"];

type FilterOptions = {
  colors: { name: string; hex: string }[];
  sizes: string[];
  dress_styles: string[];
};

export function FiltersSidebar({
  categories,
  filters,
  priceMax,
  onPriceMaxChange,
  onChange,
  onApply,
  onClose,
  className,
}: Props) {
  const [options, setOptions] = useState<FilterOptions>({
    colors: PRODUCT_COLORS.map((c) => ({ name: c.name, hex: c.hex })),
    sizes: [...PRODUCT_SIZES],
    dress_styles: [...DRESS_STYLES],
  });

  useEffect(() => {
    apiRequest<FilterOptions>("/filter-options")
      .then(({ data }) => {
        if (data?.colors?.length) {
          setOptions({
            colors: data.colors,
            sizes: data.sizes?.length ? data.sizes : [...PRODUCT_SIZES],
            dress_styles: data.dress_styles?.length ? data.dress_styles : [...DRESS_STYLES],
          });
        }
      })
      .catch(() => undefined);
  }, []);

  const typeCategories = (categories.length
    ? categories.filter((c) => FILTER_CATEGORY_SLUGS.includes(c.slug))
    : [
        { id: 1, name: "T-shirts", slug: "t-shirts" },
        { id: 2, name: "Shorts", slug: "shorts" },
        { id: 3, name: "Shirts", slug: "shirts" },
        { id: 4, name: "Hoodie", slug: "hoodie" },
        { id: 5, name: "Jeans", slug: "jeans" },
      ]
  );

  return (
    <aside
      className={cn(
        "rounded-[20px] border border-black/10 bg-white px-5 py-5 sm:px-6 sm:py-5",
        className,
      )}
    >
      <div className="mb-5 flex items-center justify-between border-b border-black/10 pb-5">
        <h2 className="text-xl font-normal">Filters</h2>
        <div className="flex items-center gap-2">
          <SlidersHorizontal className="h-5 w-5 text-black/40" />
          {onClose ? (
            <button type="button" aria-label="Close filters" onClick={onClose}>
              <X className="h-5 w-5" />
            </button>
          ) : null}
        </div>
      </div>

      <div className="space-y-4 border-b border-black/10 pb-5">
        {typeCategories.map((c) => (
          <button
            key={c.id}
            type="button"
            onClick={() => onChange({ category: filters.category === c.slug ? "" : c.slug })}
            className={cn(
              "flex w-full items-center justify-between text-left text-base font-normal",
              filters.category === c.slug ? "text-black" : "text-black/60",
            )}
          >
            {c.name}
            <ChevronDown className="h-4 w-4 -rotate-90 opacity-40" />
          </button>
        ))}
      </div>

      <div className="border-b border-black/10 py-5">
        <button type="button" className="mb-4 flex w-full items-center justify-between">
          <h3 className="text-xl font-normal">Price</h3>
          <ChevronDown className="h-4 w-4" />
        </button>
        <input
          type="range"
          min={50}
          max={5000}
          step={50}
          value={priceMax}
          onChange={(e) => onPriceMaxChange(Number(e.target.value))}
          className="w-full accent-black"
        />
        <div className="mt-2 flex justify-between text-sm font-normal">
          <span>$50</span>
          <span>${priceMax}</span>
        </div>
      </div>

      <div className="border-b border-black/10 py-5">
        <button type="button" className="mb-4 flex w-full items-center justify-between">
          <h3 className="text-xl font-normal">Colors</h3>
          <ChevronDown className="h-4 w-4" />
        </button>
        <div className="grid grid-cols-5 gap-3">
          {options.colors.map((c) => (
            <button
              key={c.name}
              type="button"
              aria-label={c.name}
              onClick={() => onChange({ color: filters.color === c.name ? "" : c.name })}
              className={cn(
                "relative h-[37px] w-[37px] rounded-full border border-black/20",
                filters.color === c.name && "ring-2 ring-black ring-offset-2",
              )}
              style={{ backgroundColor: c.hex }}
            >
              {filters.color === c.name ? (
                <span className="absolute inset-0 flex items-center justify-center text-sm text-white drop-shadow">
                  ✓
                </span>
              ) : null}
            </button>
          ))}
        </div>
      </div>

      <div className="border-b border-black/10 py-5">
        <button type="button" className="mb-4 flex w-full items-center justify-between">
          <h3 className="text-xl font-normal">Size</h3>
          <ChevronDown className="h-4 w-4" />
        </button>
        <div className="flex flex-wrap gap-2">
          {options.sizes.map((size) => (
            <button
              key={size}
              type="button"
              onClick={() => onChange({ size: filters.size === size ? "" : size })}
              className={cn(
                "rounded-full px-4 py-2.5 text-sm font-normal transition",
                filters.size === size
                  ? "bg-black text-white"
                  : "bg-surface text-black/60 hover:bg-black/5",
              )}
            >
              {size}
            </button>
          ))}
        </div>
      </div>

      <div className="py-5">
        <button type="button" className="mb-4 flex w-full items-center justify-between">
          <h3 className="text-xl font-normal">Dress Style</h3>
          <ChevronDown className="h-4 w-4" />
        </button>
        <div className="space-y-4">
          {options.dress_styles.map((style) => (
            <button
              key={style}
              type="button"
              onClick={() =>
                onChange({ dress_style: filters.dress_style === style ? "" : style })
              }
              className={cn(
                "flex w-full items-center justify-between text-left text-base font-normal",
                filters.dress_style === style ? "text-black" : "text-black/60",
              )}
            >
              {style}
              <ChevronDown className="h-4 w-4 -rotate-90 opacity-40" />
            </button>
          ))}
        </div>
      </div>

      <button
        type="button"
        onClick={onApply}
        className="mt-2 h-12 w-full rounded-full bg-black text-sm font-normal text-white transition hover:bg-black/80"
      >
        Apply Filter
      </button>
    </aside>
  );
}
