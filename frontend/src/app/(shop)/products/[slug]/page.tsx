"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { Minus, Plus } from "lucide-react";
import { apiRequest } from "@/lib/api";
import { resolveMediaUrl } from "@/lib/media";
import type { Product, ProductDetailPayload } from "@/lib/types";
import { formatCurrency } from "@/lib/format";
import { SafeDescription } from "@/components/shop/SafeDescription";
import { ProductCard } from "@/components/shop/ProductCard";
import { StarRating, ratingFromId } from "@/components/shop/StarRating";
import { ProductReviews } from "@/components/shop/ProductReviews";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { useCart } from "@/hooks/useCart";

export default function ProductDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const [product, setProduct] = useState<Product | null>(null);
  const [related, setRelated] = useState<Product[]>([]);
  const [qty, setQty] = useState(1);
  const [activeImage, setActiveImage] = useState(0);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const { addToCart } = useCart();

  useEffect(() => {
    setLoading(true);
    apiRequest<ProductDetailPayload>(`/products/${slug}`)
      .then(({ data }) => {
        setProduct(data.product);
        setRelated(data.related);
        setActiveImage(0);
      })
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return (
      <div className="flex justify-center py-24">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  if (!product) {
    return (
      <div className="container-shop py-20 text-center">
        <p>Product not found.</p>
        <Link href="/products" className="mt-4 inline-block underline">
          Back to shop
        </Link>
      </div>
    );
  }

  const images = (product.images.length
    ? product.images
    : product.thumbnail
      ? [{ id: 0, url: product.thumbnail, alt: product.name, is_primary: true }]
      : []
  )
    .map((img) => ({ ...img, url: resolveMediaUrl(img.url) ?? "" }))
    .filter((img) => Boolean(img.url));

  const discount =
    product.compare_at_price && product.compare_at_price > product.price
      ? Math.round(((product.compare_at_price - product.price) / product.compare_at_price) * 100)
      : 0;

  async function handleAdd() {
    setAdding(true);
    setMessage(null);
    try {
      await addToCart(product!.id, qty);
      setMessage("Added to cart");
    } catch (err) {
      setMessage(err instanceof Error ? err.message : "Could not add to cart");
    } finally {
      setAdding(false);
    }
  }

  return (
    <div className="container-shop py-8 sm:py-10">
      <p className="text-sm text-black/60">
        <Link href="/" className="hover:text-black">
          Home
        </Link>{" "}
        &gt;{" "}
        {product.category ? (
          <>
            <Link href={`/products?category=${product.category.slug}`} className="hover:text-black">
              {product.category.name}
            </Link>{" "}
            &gt;{" "}
          </>
        ) : null}
        <span className="text-black">{product.name}</span>
      </p>

      <div className="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-10">
        <div className="flex flex-col-reverse gap-3 sm:flex-row">
          {images.length > 1 ? (
            <div className="flex gap-3 overflow-x-auto sm:w-[152px] sm:flex-col sm:overflow-visible">
              {images.map((img, idx) => (
                <button
                  key={img.id}
                  type="button"
                  onClick={() => setActiveImage(idx)}
                  className={`relative h-[106px] w-[111px] shrink-0 overflow-hidden rounded-[20px] bg-card-bg sm:h-[167px] sm:w-full ${
                    idx === activeImage ? "ring-2 ring-black" : ""
                  }`}
                >
                  <Image src={img.url} alt="" fill unoptimized className="object-cover" sizes="152px" />
                </button>
              ))}
            </div>
          ) : null}
          <div className="relative min-h-[300px] flex-1 overflow-hidden rounded-[20px] bg-card-bg sm:min-h-[530px]">
            {images[activeImage] ? (
              <Image
                src={images[activeImage].url}
                alt={images[activeImage].alt ?? product.name}
                fill
                unoptimized
                className="object-cover"
                sizes="(max-width: 1024px) 100vw, 50vw"
                priority
              />
            ) : null}
          </div>
        </div>

        <div>
          <h1 className="font-display text-3xl font-bold uppercase leading-tight sm:text-[40px]">
            {product.name}
          </h1>
          <StarRating
            rating={product.average_rating ?? ratingFromId(product.id)}
            className="mt-3"
          />
          {product.reviews_count ? (
            <p className="mt-1 text-sm text-black/60">{product.reviews_count} reviews</p>
          ) : null}
          <div className="mt-4 flex flex-wrap items-center gap-3">
            <span className="text-2xl font-bold sm:text-[32px]">{formatCurrency(product.price)}</span>
            {product.compare_at_price ? (
              <>
                <span className="text-2xl font-bold text-black/40 line-through sm:text-[32px]">
                  {formatCurrency(product.compare_at_price)}
                </span>
                {discount > 0 ? (
                  <span className="rounded-full bg-sale-soft px-3.5 py-1.5 text-sm font-medium text-sale">
                    -{discount}%
                  </span>
                ) : null}
              </>
            ) : null}
          </div>
          <div className="mt-5 border-b border-black/10 pb-6 text-sm text-black/60">
            <SafeDescription text={product.description} />
          </div>

          <p className="mt-5 text-sm text-black/60">
            {product.in_stock ? (
              <>
                Stock status: <span className="font-medium text-black">In stock ({product.stock})</span>
              </>
            ) : (
              <span className="font-medium text-sale">Out of stock</span>
            )}
          </p>
          <p className="mt-1 text-sm text-black/60">
            SKU: <span className="text-black">{product.sku}</span>
          </p>
          {product.dress_style ? (
            <p className="mt-1 text-sm text-black/60">
              Dress style: <span className="text-black">{product.dress_style}</span>
            </p>
          ) : null}
          {product.color ? (
            <p className="mt-1 text-sm text-black/60">
              Color: <span className="text-black">{product.color}</span>
            </p>
          ) : null}
          {product.size ? (
            <p className="mt-1 text-sm text-black/60">
              Size: <span className="text-black">{product.size}</span>
            </p>
          ) : null}

          <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-black/10 pt-6">
            <div className="flex items-center gap-5 rounded-full bg-surface px-5 py-3.5">
              <button type="button" onClick={() => setQty((q) => Math.max(1, q - 1))} aria-label="Decrease">
                <Minus className="h-5 w-5" />
              </button>
              <span className="min-w-6 text-center font-medium">{qty}</span>
              <button
                type="button"
                onClick={() => setQty((q) => Math.min(product.stock || 1, q + 1))}
                aria-label="Increase"
              >
                <Plus className="h-5 w-5" />
              </button>
            </div>
            <Button
              onClick={handleAdd}
              disabled={!product.in_stock || adding}
              size="lg"
              className="min-w-[200px] flex-1"
            >
              {adding ? "Adding…" : "Add to Cart"}
            </Button>
          </div>
          {message ? <p className="mt-3 text-sm text-black/60">{message}</p> : null}
        </div>
      </div>

      <ProductReviews
        slug={product.slug}
        averageRating={product.average_rating ?? null}
        reviewsCount={product.reviews_count ?? 0}
      />

      {related.length ? (
        <section className="mt-16 sm:mt-20">
          <h2 className="section-title">You might also like</h2>
          <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-5">
            {related.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </section>
      ) : null}
    </div>
  );
}
