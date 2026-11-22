# Nocfinity E-Commerce API

Base URL: `http://localhost:8000/api/v1`

## Response envelope

All JSON responses use:

```json
{
  "success": true,
  "message": "Human-readable summary",
  "data": {},
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  },
  "errors": {
    "field": ["Validation message"]
  }
}
```

| HTTP | Meaning |
|------|---------|
| 200 / 201 | Success |
| 401 | Unauthenticated / invalid credentials (always generic for login) |
| 403 | Authenticated but not allowed (permission / ability) |
| 404 | Not found (also used for IDOR on foreign orders) |
| 422 | Validation failed |
| 429 | Rate limited (`retry_after` may be present) |

## Auth headers

- Customer / staff API: `Authorization: Bearer {token}`
- Guest cart: `X-Cart-Token: {uuid}` (also returned as response header `X-Cart-Token`)

Staff tokens from `POST /admin/login` include Sanctum ability `admin`. Customer tokens use `customer`. Admin routes require both a staff role and the `admin` ability.

### Password policy

Min 8 chars with upper, lower, number, and special character (customers and staff create/update).

---

## Public

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check |
| GET | `/products` | Paginated catalog. Query: `search`, `category` (id/slug), `min_price`, `max_price`, `color`, `size`, `sort` (`newest\|price_asc\|price_desc\|name`), `page`, `per_page`, `active_only` |
| GET | `/products/{slug\|id}` | Product detail + related |
| GET | `/categories` | Tree (`tree=1` default) or flat page (`tree=0`) |
| POST | `/register` | Customer register → Sanctum `customer` token + verification email |
| POST | `/login` | Customer login. Failure always `401` + `Invalid email or password.` (including staff emails). Rate-limited after 5 attempts. |
| POST | `/admin/login` | Staff login → Sanctum `admin` token, or `{ requires_two_factor, challenge_token }` when TOTP enabled |
| POST | `/admin/login/2fa` | Complete staff login with TOTP/recovery code + `challenge_token` |
| GET | `/products/{slug}/reviews` | Approved reviews + average rating meta |
| POST | `/forgot-password` | Always generic success (throttle 5/min) |
| POST | `/reset-password` | Reset with email token (throttle 5/min) |
| GET | `/email/verify/{id}/{hash}` | Signed verification link |
| POST | `/email/resend` | Resend verification (auth, throttle 6/min) |

---

## Cart (guest or auth)

| Method | Path | Body |
|--------|------|------|
| GET | `/cart` | — |
| POST | `/cart/items` | `{ "product_id": 1, "quantity": 1 }` only — **prices ignored** |
| PUT | `/cart/items/{id}` | `{ "quantity": 2 }` |
| DELETE | `/cart/items/{id}` | — |
| POST | `/cart/coupon` | `{ "code": "WELCOME10" }` |
| DELETE | `/cart/coupon` | — |

**Totals are always computed on the server from DB product prices.** Client-sent prices are ignored. Coupons are validated (active, dates, usage limit, minimum order).

---

## Customer (auth)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/user` | Current user + roles/permissions (never password) |
| POST | `/logout` | Revoke current token |
| POST | `/checkout` | Create pending order + Stripe PaymentIntent (`capture_method=manual`). Body: shipping address + optional note only |
| POST | `/checkout/confirm` | `{ "order_id", "payment_intent_id" }` — re-checks stock & coupon, captures, decrements inventory |
| GET | `/orders` | Own orders only |
| GET | `/orders/{id}` | Own order; **404 if not owner (IDOR protection)** |
| POST | `/products/{slug}/reviews` | Create review (auth customer who purchased; rating 1–5) |

### Admin 2FA (auth + staff)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin/2fa` | Status |
| POST | `/admin/2fa/setup` | Generate secret + QR SVG |
| POST | `/admin/2fa/confirm` | `{ "code" }` → enable + recovery codes |
| DELETE | `/admin/2fa` | `{ "password", "code" }` disable |

### Checkout example

```http
POST /api/v1/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "shipping_address": {
    "name": "Ada Lovelace",
    "phone": "+1 555 0100",
    "line1": "123 Demo St",
    "line2": null,
    "city": "Austin",
    "state": "TX",
    "postal_code": "78701",
    "country": "US"
  },
  "customer_note": "Leave at door"
}
```

Success `201`:

```json
{
  "success": true,
  "message": "Checkout initiated.",
  "data": {
    "order": { "id": 1, "order_number": "ORD-2026-000001", "total_amount": 120.0, "status": "pending" },
    "client_secret": "pi_..._secret_...",
    "payment_intent_id": "pi_..."
  }
}
```

Confirm:

```http
POST /api/v1/checkout/confirm
{ "order_id": 1, "payment_intent_id": "pi_..." }
```

---

## Admin (auth + staff + `admin` ability + permission)

Prefix: `/admin`

| Method | Path | Permission |
|--------|------|------------|
| POST | `/logout` | staff |
| GET | `/me` | staff |
| PUT | `/profile` | staff (name / password) |
| GET | `/dashboard` | `reports.view` — today's orders/revenue, customers, low stock, 30-day sales, recent orders |
| GET | `/products` | `products.view` |
| GET | `/products/{id}` | `products.view` |
| POST | `/products` | `products.create` — JSON body + optional `image_urls[]` |
| PUT | `/products/{id}` | `products.edit` — sending `image_urls` replaces gallery |
| DELETE | `/products/{id}` | `products.delete` |
| GET/POST/PUT/DELETE | `/categories`… | `categories.*` — `parent_id` for nesting |
| GET | `/orders` | `orders.view` (`status`, `search`, pagination) |
| GET | `/orders/{id}` | `orders.view` — includes `allowed_transitions` |
| PATCH | `/orders/{id}/status` | `orders.update_status` — enforced lifecycle |
| GET/POST/PUT/DELETE | `/coupons`… | `coupons.manage` |
| GET | `/customers` | `customers.view` |
| GET | `/customers/{id}` | `customers.view` — orders + stats |
| GET/POST/PUT/DELETE | `/roles`… | `roles.manage` |
| GET/POST | `/staff` | `roles.manage` |
| PUT | `/staff/{id}/roles` | `roles.manage` |

### Order status transitions

`pending → (payment) paid → processing → shipped → delivered`  
From most states: `cancelled` (paid cancels restore stock once).  
Admins cannot manually set `paid`.

Permissions are Spatie names such as `products.create`, `orders.update_status`, `coupons.manage`, `roles.manage`.

---

## Security notes

- Checkout never trusts client prices or totals.
- Expired / invalid / over-limit coupons are rejected server-side (apply, initiate, merge, confirm).
- Login failures always return `Invalid email or password.`
- Order show checks ownership (IDOR → 404).
- Passwords hashed via Laravel `hashed` cast (bcrypt).
- HTML stripped from product/category name & description on write.
- API responses never include password hashes or `STRIPE_SECRET` (checkout returns Stripe `client_secret` only).
- Security headers middleware on Laravel; Next.js adds CSP + browser headers.
- CSRF applies to session/Blade routes; SPA uses Bearer tokens.

## Stripe test cards

- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Any future expiry, any CVC, any ZIP
