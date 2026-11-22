import Link from "next/link";
import Image from "next/image";
import { formatCurrency } from "@/lib/format";
import { StarRating } from "@/components/shop/StarRating";

export type FigmaProduct = {
  name: string;
  price: number;
  compareAt: number | null;
  rating: number;
  image: string;
  href: string;
};

export function FigmaProductCard({ product }: { product: FigmaProduct }) {
  const discount =
    product.compareAt && product.compareAt > product.price
      ? Math.round(((product.compareAt - product.price) / product.compareAt) * 100)
      : 0;

  return (
    <div className="group flex min-w-[198px] flex-col gap-2.5 sm:min-w-0">
      <Link
        href={product.href}
        className="relative aspect-square overflow-hidden rounded-[20px] bg-card-bg"
      >
        <Image
          src={product.image}
          alt={product.name}
          fill
          sizes="(max-width: 768px) 50vw, 25vw"
          className="object-cover transition duration-300 group-hover:scale-105"
        />
      </Link>
      <div className="flex flex-col gap-1">
        <Link href={product.href} className="block">
          <h3 className="text-base font-normal leading-snug text-black line-clamp-1 transition hover:underline sm:text-xl">
            {product.name}
          </h3>
        </Link>
        <StarRating rating={product.rating} />
        <div className="flex flex-wrap items-center gap-2.5">
          <span className="text-xl font-normal sm:text-2xl">{formatCurrency(product.price)}</span>
          {product.compareAt ? (
            <>
              <span className="text-xl font-normal text-black/40 line-through sm:text-2xl">
                {formatCurrency(product.compareAt)}
              </span>
              {discount > 0 ? (
                <span className="rounded-full bg-sale-soft px-3.5 py-1.5 text-xs font-medium text-sale">
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
