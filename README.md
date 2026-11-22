# Nocfinity E-Commerce Assessment

Full-stack mini e-commerce: **Laravel 12 API** (Sanctum + Spatie Permission + Stripe) and **Next.js 16** storefront + admin panel.

**Niche / design:** fashion apparel matching the SHOP.CO Figma freebie (Men / Women category tree, 20 seeded products with images).

## Stack

- Backend: Laravel 12, Sanctum, Spatie Laravel Permission, Stripe PHP SDK, MySQL 8
- Frontend: Next.js App Router, TypeScript, Tailwind CSS 4
- Mail: Mailpit (SMTP `1025`, UI `http://localhost:8025`)
- Payments: Stripe Test Mode (manual capture)

## Quick start

### 1. Infrastructure

```bash
docker compose up -d
```

Starts MySQL 8 (`3306`) and Mailpit (`1025` / `8025`).

If Docker is unavailable, create a local MySQL database named `nocfinity_ecommerce` and point `.env` at it.

### 2. Backend

```bash
cd backend
cp .env.example .env
# set DB_*, STRIPE_SECRET, STRIPE_KEY (see Environment variables)
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

API: `http://localhost:8000/api/v1`

### 3. Frontend

```bash
cd frontend
cp .env.example .env.local
# NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
# NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
npm install
npm run dev
```

Storefront: `http://localhost:3000`  
Admin: `http://localhost:3000/admin/login`

Production-style frontend build:

```bash
cd frontend
npm run build
npm run start
```

## Environment variables

### Backend (`backend/.env`)

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Laravel base URL (`http://localhost:8000`) |
| `FRONTEND_URL` | Next.js origin (CORS + password-reset links) |
| `DB_*` | MySQL connection |
| `MAIL_HOST` / `MAIL_PORT` | Mailpit (`127.0.0.1` / `1025`) |
| `STRIPE_KEY` / `STRIPE_SECRET` | Stripe publishable + secret |
| `STRIPE_WEBHOOK_SECRET` | Reserved for future webhooks (not required for local confirm flow) |
| `STRIPE_CURRENCY` | Default `usd` |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000,127.0.0.1:3000` |
| `LOW_STOCK_THRESHOLD` | Admin dashboard low-stock count |
| `BCRYPT_ROUNDS` | Password hashing cost (default 12) |

### Frontend (`frontend/.env.local`)

| Variable | Purpose |
|----------|---------|
| `NEXT_PUBLIC_API_URL` | `http://localhost:8000/api/v1` |
| `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` | Stripe publishable key |

## Seeded accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@nocfinity.test` | `Admin@12345` |
| Admin | `admin.ops@nocfinity.test` | `Staff@12345` |
| Product Manager | `manager@nocfinity.test` | `Staff@12345` |
| Order Manager | `orders@nocfinity.test` | `Staff@12345` |
| Support | `support@nocfinity.test` | `Staff@12345` |
| Customer | `customer@nocfinity.test` | `Customer@123` |

Password policy (customers + staff): min 8 chars with upper, lower, number, and special character.

Sample coupons: `WELCOME10` (10%), `SAVE20` ($20). `EXPIRED5` exists to demo rejection.

Catalog: 2-level categories (Men/Women → children) and **20 products** with images. Sample orders cover `pending`, `paid`, `processing`, `shipped`, `delivered`.

## Stripe test cards

- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Expiry: any future date · CVC: any 3 digits

## Database schema (minimum)

| Table | Notes |
|-------|-------|
| `users`, `password_reset_tokens`, `sessions` | Customers + staff (staff identified by roles) |
| `personal_access_tokens` | Sanctum API tokens |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie RBAC (morph pivots instead of `role_user`) |
| `categories`, `products`, `product_images` | Nested categories via `parent_id` |
| `carts`, `cart_items` | Guest (`session_id`) or user carts + coupon |
| `orders`, `order_items` | Lifecycle + `stock_decremented` / `stock_restored` |
| `coupons` | Percentage / fixed |
| `payments` | Stripe PaymentIntent records |

`activity_logs` was skipped (bonus) — see below.

## Architecture notes

### Folder structure

- `backend/` — JSON API. Domain logic in `app/Services` (cart totals, checkout, order numbers).
- `frontend/` — Next.js App Router. `(shop)` for customers, `admin` for staff.
- `docs/API.md` — endpoint reference.
- `docker-compose.yml` — MySQL + Mailpit.

### Order totals & stock security

- Cart/checkout **never** trust client prices or totals (only `product_id` + `quantity` + shipping address).
- Line prices come from locked product rows (`SELECT … FOR UPDATE`).
- Coupons re-validated on apply, checkout initiate, cart merge, and payment confirm.
- Stripe PaymentIntents use **manual capture**: authorize → re-check stock/coupon → capture → decrement stock.
- Cancelled paid orders restore stock once.
- Order numbers: `ORD-YYYY-000123`.

### RBAC

- Spatie roles/permissions; API uses `permission:…` middleware (not role-name checks).
- Admin UI hides nav/actions from effective permissions.
- Super Admin role/user cannot be edited or deleted by others.

### Security (3.6)

- Passwords: Laravel `hashed` cast (bcrypt) + strong policy.
- Eloquent/query builder only for user filters (no interpolated raw SQL).
- Product/category names & descriptions strip HTML on write; storefront renders text (React escapes / `SafeDescription`).
- Generic login errors (`Invalid email or password.`) — no email/password enumeration.
- Order IDOR: customer `GET /orders/{id}` returns 404 if not owner.
- API hides password hashes; Stripe secret never returned (only PaymentIntent `client_secret`).
- Security headers on Laravel + Next.js (CSP, frame denial, nosniff, …).
- CSRF: session/Blade web routes use Laravel CSRF; SPA uses Bearer Sanctum tokens (stateless API).
- Frontend mirrors password / shipping rules client-side; **backend is source of truth**.

### Guest cart

- Guests get `X-Cart-Token`. On login/register the guest cart merges into the user cart (coupon re-validated).

## Tests

```bash
cd backend
php artisan test
```

Covered: RBAC middleware, order IDOR, stock/coupon rejection, login throttle, paid-order stock decrement, security hardening (price ignore, XSS strip, generic staff login).

## Bonus / skipped items

| Item | Status |
|------|--------|
| Guest cart merge | Implemented |
| Docker Compose (MySQL + Mailpit) | Implemented |
| Feature tests (checkout/stock/RBAC/security) | Implemented |
| Email verification on registration | Implemented (VerifyEmail + Mailpit; not required to checkout) |
| 2FA (TOTP) | Skipped — timeboxed; would add `pragmarx/google2fa` on admin login |
| Product reviews | Skipped — timeboxed |
| Activity logs | Skipped — timeboxed; would log admin mutations |
| Live deploy | Skipped — local demo focus |
| Dark mode toggle | Skipped — bonus not required |
| Walkthrough video | Optional — not included in repo |

## What I would improve with more time

- Stripe webhooks as the source of truth (idempotent handlers)
- Image uploads to S3 + admin media library
- Full Figma token pass (exact typography / spacing audit)
- Activity log for admin mutations
- Broader coverage for concurrent stock races
- Admin TOTP 2FA

## API docs

See [docs/API.md](docs/API.md).
