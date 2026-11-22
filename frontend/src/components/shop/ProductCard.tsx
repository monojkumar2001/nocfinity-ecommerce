import Link from "next/link";
import Image from "next/image";
import { formatCurrency } from "@/lib/format";
import { resolveMediaUrl } from "@/lib/media";
import type { Product } from "@/lib/types";
import { StarRating, ratingFromId } from "@/components/shop/StarRating";

export function ProductCard({ product }: { product: Product }) {
  const image = resolveMediaUrl(product.thumbnail ?? product.images[0]?.url);
  const rating = product.average_rating ?? ratingFromId(product.id);
  const discount =
    product.compare_at_price && product.compare_at_price > product.price
      ? Math.round(((product.compare_at_price - product.price) / product.compare_at_price) * 100)
      : 0;

  const href = `/products/${product.slug}`;

  return (
    <div className="group flex flex-col gap-3">
      <Link href={href} className="relative aspect-square overflow-hidden rounded-[20px] bg-card-bg">
        {image ? (
          <Image
            src={image}
            alt={product.name}
            fill
            unoptimized
            sizes="(max-width: 768px) 50vw, 25vw"
            className="object-cover transition duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-black/40">No image</div>
        )}
        {!product.in_stock ? (
          <span className="absolute left-3 top-3 rounded-full bg-black px-3 py-1 text-xs text-white">
            Out of stock
          </span>
        ) : null}
      </Link>
      <div className="flex flex-col gap-1.5">
        <Link href={href} className="block">
          <h3 className="text-base font-normal text-black line-clamp-1 transition hover:underline sm:text-lg">
            {product.name}
          </h3>
        </Link>
        <StarRating rating={rating} />
        <div className="flex flex-wrap items-center gap-2.5">
          <span className="text-xl font-normal text-black sm:text-2xl">{formatCurrency(product.price)}</span>
          {product.compare_at_price ? (
            <>
              <span className="text-xl font-normal text-black/40 line-through sm:text-2xl">
                {formatCurrency(product.compare_at_price)}
              </span>
              {discount > 0 ? (
                <span className="rounded-full bg-sale-soft px-3 py-1 text-xs font-medium text-sale">
                  -{discount}%
                </span>
              ) : null}
            </>
          ) : null}
        </div>
      </div>
    </div>
  );
}
