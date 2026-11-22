"use client";

import { useEffect, useState } from "react";
import { apiRequest } from "@/lib/api";
import { getToken, getUser, isStaff } from "@/lib/auth";
import { StarRating } from "@/components/shop/StarRating";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";

type Review = {
  id: number;
  rating: number;
  title: string | null;
  body: string | null;
  created_at?: string;
  user: { id?: number; name?: string | null };
};

type ReviewsMeta = {
  average_rating?: number;
  reviews_count?: number;
  current_page: number;
  last_page: number;
};

export function ProductReviews({
  slug,
  averageRating,
  reviewsCount,
}: {
  slug: string;
  averageRating: number | null;
  reviewsCount: number;
}) {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [meta, setMeta] = useState<ReviewsMeta | null>(null);
  const [rating, setRating] = useState(5);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const authed = Boolean(getToken());
  const staff = isStaff(getUser());

  async function load() {
    const { data, meta: responseMeta } = await apiRequest<Review[]>(`/products/${slug}/reviews`);
    setReviews(data);
    setMeta(responseMeta as ReviewsMeta);
  }

  useEffect(() => {
    load().catch(() => undefined);
  }, [slug]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setMessage(null);
    setSubmitting(true);
    try {
      await apiRequest(`/products/${slug}/reviews`, {
        method: "POST",
        body: JSON.stringify({ rating, title: title || null, body: body || null }),
      });
      setTitle("");
      setBody("");
      setRating(5);
      setMessage("Thanks for your review.");
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not submit review");
    } finally {
      setSubmitting(false);
    }
  }

  const avg = meta?.average_rating ?? averageRating;
  const count = meta?.reviews_count ?? reviewsCount;

  return (
    <section className="mt-16 border-t border-black/10 pt-10 sm:mt-20">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h2 className="section-title">Reviews</h2>
          <div className="mt-3 flex items-center gap-3">
            {avg ? <StarRating rating={avg} /> : <p className="text-sm text-black/60">No ratings yet</p>}
            <span className="text-sm text-black/60">({count} reviews)</span>
          </div>
        </div>
      </div>

      <div className="mt-8 space-y-4">
        {reviews.length === 0 ? (
          <p className="text-sm text-black/60">Be the first to review this product.</p>
        ) : (
          reviews.map((review) => (
            <article key={review.id} className="rounded-2xl border border-black/10 bg-white p-5">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-medium">{review.user.name ?? "Customer"}</p>
                <StarRating rating={review.rating} />
              </div>
              {review.title ? <p className="mt-2 font-medium">{review.title}</p> : null}
              {review.body ? <p className="mt-1 text-sm text-black/70">{review.body}</p> : null}
            </article>
          ))
        )}
      </div>

      {authed && !staff ? (
        <form onSubmit={submit} className="mt-8 max-w-xl space-y-4 rounded-2xl border border-black/10 bg-white p-5">
          <h3 className="font-display text-lg font-semibold">Write a review</h3>
          <p className="text-xs text-black/50">Only customers who purchased this item can submit a review.</p>
          <label className="block text-sm">
            Rating
            <select
              className="mt-1 w-full rounded-lg border border-border px-3 py-2"
              value={rating}
              onChange={(e) => setRating(Number(e.target.value))}
            >
              {[5, 4, 3, 2, 1].map((value) => (
                <option key={value} value={value}>
                  {value} stars
                </option>
              ))}
            </select>
          </label>
          <Input label="Title" value={title} onChange={(e) => setTitle(e.target.value)} />
          <label className="block text-sm">
            Review
            <textarea
              className="mt-1 w-full rounded-lg border border-border px-3 py-2"
              rows={4}
              value={body}
              onChange={(e) => setBody(e.target.value)}
            />
          </label>
          {error ? <p className="text-sm text-red-600">{error}</p> : null}
          {message ? <p className="text-sm text-green-700">{message}</p> : null}
          <Button type="submit" disabled={submitting}>
            {submitting ? "Submitting…" : "Submit review"}
          </Button>
        </form>
      ) : null}
    </section>
  );
}
