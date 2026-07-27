# Directorist – Affiliate

Affiliate tracking and fixed-commission referral system for [Directorist](https://directorist.com/). It lets people apply to become affiliates, hands each approved affiliate a referral link, tracks visits through that link with cookies, and records a fixed commission whenever a referred visitor **registers an account** or **submits/publishes a listing**. Admins moderate affiliates and referrals, record manual payouts, and export approved commissions as CSV.

## At a glance

| Item | Value |
| --- | --- |
| Version | 1.0.0 (plugin) / 0.2.0 (DB schema) |
| Author | [wpXplore](https://wpxplore.com) |
| Website | https://wpxplore.com/tools/directorist-affiliate/ |
| Requires | WordPress 5.8+, PHP 7.4+ |
| Depends on | Directorist ≥ 8.7.3 (declared via `Requires Plugins: directorist`) |
| Text domain | `directorist-affiliate` — fully translatable; POT template at `languages/directorist-affiliate.pot` |
| Commission model | Fixed amounts for registration/listing events; **fixed or percentage of order total** for paid plan/featured orders; all filterable |
| Revenue events | Pricing-plan purchases (requires Pricing Plans extension) and featured-listing purchases (requires Directorist monetization) — auto-disabled when the dependency is missing |
| Payouts | Manual (recorded by admin; no gateway integration); per-affiliate minimum enforced on bulk payouts |
| Assets | One shared stylesheet + one vanilla JS file (AJAX forms, copy-referral-link button) |
| Forms | All four forms submit via AJAX (`admin-ajax.php`) with full no-JavaScript POST fallbacks |
| User guide | [DOCUMENTATION.md](DOCUMENTATION.md) |

## How it works (big picture)

```mermaid
flowchart TD
    A[Visitor opens site with ?ref=CODE] --> B[Tracking: validate code belongs to an approved affiliate]
    B --> C[Visit row saved + 2 cookies set for N days]
    C --> D{Visitor converts}
    D -->|Registers an account| E[Referral: user_registration, status pending]
    D -->|Submits or publishes a listing| F[Referral: listing_submission, status pending]
    E --> G[Admin approves / rejects referral]
    F --> G
    G -->|Approved| H[Admin marks paid → payout record + CSV export]
```

1. **Application** – A visitor applies via the `[directorist_affiliate_registration]` shortcode (or an admin adds them manually). A WordPress user is created for them if needed. The application starts as `pending`.
2. **Approval** – An admin approves the affiliate; they get a unique referral code and a referral URL like `https://site.com/?ref=CODE`.
3. **Tracking** – When someone lands on any front-end page with `?ref=CODE` (of an approved affiliate), a visit row is logged and two cookies are set for the configured duration.
4. **Conversion** – If that visitor registers, or submits/publishes a listing, a referral row with a fixed commission amount is created (`pending`), the visit is flagged converted, and the affiliate is emailed.
5. **Moderation & payout** – The admin approves referrals, then marks them paid (individually or in bulk), which writes a payout record. Approved commissions can be exported to CSV for processing in a payment tool.

## File structure

```
directorist-affiliate/
├── directorist-affiliate.php            # Bootstrap: constants, autoloader, activation hooks, boot
├── uninstall.php                        # Opt-in data removal on plugin delete
├── .gitignore
├── README.md                            # Technical reference (this file)
├── DOCUMENTATION.md                     # Site-owner user guide
├── docs/
│   └── images/                          # Screenshots/video thumbnail referenced by DOCUMENTATION.md
├── includes/
│   ├── class-autoloader.php             # Classmap autoloader (lazy class loading)
│   ├── class-plugin.php                 # Service container / dependency checks
│   ├── class-activator.php              # dbDelta table creation + default options
│   ├── class-deactivator.php            # flush_rewrite_rules only
│   ├── class-view.php                   # Shared view/template renderer
│   ├── class-registration.php           # Application processing (public + admin flows)
│   ├── class-ajax.php                   # AJAX endpoints for all forms
│   ├── class-settings.php               # Settings read/sanitize/save
│   ├── class-affiliate.php              # Affiliate repository (CRUD, codes, user helper)
│   ├── class-referral.php               # Referral repository (CRUD, sums, dedupe)
│   ├── class-tracking.php               # ?ref= capture, cookies, visit rows
│   ├── class-commission.php             # Fixed commission amount resolution (filterable)
│   ├── class-payout.php                 # Manual payout records
│   ├── class-email.php                  # wp_mail notifications
│   ├── class-shortcodes.php             # Registration + dashboard shortcodes
│   ├── class-directorist-integration.php# Registration/listing referrals + dashboard tab
│   └── class-order-integration.php      # Paid-order commissions (new + legacy orders), refund reversal
├── admin/
│   ├── class-admin.php                  # Menu, assets, screen rendering
│   ├── class-admin-actions.php          # Form/moderation handlers + CSV export
│   └── views/                           # dashboard, affiliates, referrals,
│                                        # visits, payouts, settings pages
├── public/
│   ├── class-public.php                 # Front-end asset registration + tracking hook
│   └── views/                           # registration-form.php, affiliate-dashboard.php
├── assets/
│   ├── css/directorist-affiliate.css    # Shared front-end + admin styles (design tokens, badges, cards)
│   └── js/directorist-affiliate.js      # Copy-referral-link button
└── languages/                           # (empty)
```

Layering: `directorist-affiliate.php` → `Plugin` container → repositories/services in `includes/` → thin hook classes (`Admin`, `Admin_Actions`, `Public`, `Shortcodes`, `Directorist_Integration`) → templates in `admin/views/` + `public/views/` rendered through `Directorist_Affiliate_View`. Only the bootstrap trio (Plugin, Activator, Deactivator) plus the autoloader are loaded eagerly; every other class loads on first use via the classmap, so admin-only classes never load on front-end requests.

## Bootstrap flow

`directorist-affiliate.php` defines constants (`DIRECTORIST_AFFILIATE_VERSION`, `_DB_VERSION`, `_MIN_DIRECTORIST`, paths) and calls `Directorist_Affiliate_Plugin::boot()`, which registers:

- `plugins_loaded` → dependency check: admin notice if Directorist is missing (`ATBDP()` / `ATBDP_VERSION`) or older than **8.7.3**.
- `plugins_loaded` → text domain loading.
- `directorist_loaded` → singleton instantiation. The container (`Directorist_Affiliate_Plugin::instance()`) builds the services (`settings`, `affiliate`, `tracking`, `referral`, `commission`, `payout`, `email`, `shortcodes`) — class files load on demand via `Directorist_Affiliate_Autoloader` — and registers hook classes: `Public`, `Shortcodes`, `Directorist_Integration`, plus `Admin` and `Admin_Actions` (admin only). Hooks are skipped entirely if the installed Directorist is below the minimum version.

**Activation** hard-blocks (deactivates itself + `wp_die`) unless `directorist/directorist-base.php` is active, then creates tables via `dbDelta()` and seeds default settings. **Deactivation** only flushes rewrite rules — no data is removed. **Uninstall** (`uninstall.php`) removes the four tables, both options, and the related user meta, but only when the "Delete data on uninstall" setting is enabled; otherwise data survives uninstall.

## Database schema

Four custom tables (all `dbDelta`-managed, version tracked in option `directorist_affiliate_db_version`):

### `{prefix}directorist_affiliates`
| Column | Notes |
| --- | --- |
| `id` | PK |
| `user_id` | Linked WP user (nullable, indexed) |
| `status` | `pending` \| `approved` \| `rejected` \| `suspended` (indexed) |
| `referral_code` | **Unique**. Generated from user ID (or an 8-char random string), suffixed `-1`, `-2`… on collision |
| `payout_email`, `website`, `promotional_method`, `application_note` | Application data |
| `date_created`, `date_updated` | Timestamps |

### `{prefix}directorist_affiliate_visits`
| Column | Notes |
| --- | --- |
| `id` | PK |
| `affiliate_id`, `referral_code` | Who the visit is credited to (indexed) |
| `landing_url`, `referrer_url` | Where they landed / came from |
| `ip_address`, `user_agent` | Visitor fingerprint (IP from `REMOTE_ADDR` only) |
| `converted` | 0/1 flag (indexed), set when the visit leads to a referral |
| `referred_user_id`, `listing_id` | Filled on conversion |
| `date_created` | Timestamp (indexed) |

### `{prefix}directorist_affiliate_referrals`
| Column | Notes |
| --- | --- |
| `id` | PK |
| `affiliate_id` | Indexed |
| `referral_type` | `user_registration` \| `listing_submission` \| `plan_purchase` \| `featured_purchase` |
| `referred_user_id`, `listing_id` | Conversion subject (indexed) |
| `order_id`, `order_source`, `order_total` | For order events: the paid order (`directorist` = 8.8+ order repository, `legacy` = `atbdp_orders` post), and its total at commission time (order_id indexed) |
| `commission_amount` | `decimal(18,6)`, amount snapshotted at creation |
| `status` | `pending` \| `approved` \| `rejected` \| `paid` \| `cancelled` \| `refunded` (indexed) |
| `date_created`, `date_approved`, `date_paid`, `notes` | Lifecycle metadata |

### `{prefix}directorist_affiliate_payouts`
| Column | Notes |
| --- | --- |
| `id` | PK |
| `affiliate_id` | Indexed |
| `amount` | Sum of the included referrals' commissions |
| `status` | Always `paid` currently |
| `payment_method` | Always `manual` currently |
| `payout_email` | Snapshot of where to send money |
| `referral_ids` | Comma-separated referral IDs covered by this payout |
| `date_created`, `date_paid`, `notes` | Metadata |

## Core services

| Class | Responsibility |
| --- | --- |
| `Directorist_Affiliate_Settings` | Reads/saves the `directorist_affiliate_settings` option with defaults + sanitization (amounts normalized to `0.00` strings, trigger whitelisted, cookie days ≥ 1). |
| `Directorist_Affiliate_Affiliate` | Affiliate CRUD, status transitions, unique referral-code generation, lookups by ID / user / approved code, display name/email helpers. |
| `Directorist_Affiliate_Tracking` | Captures `?ref=` visits on `template_redirect` (priority 1), writes visit rows, sets/reads cookies, marks visits converted. |
| `Directorist_Affiliate_Referral` | Referral CRUD with **duplicate prevention** (one referral per affiliate + type + user/listing), status updates with date stamping, counts and commission sums. |
| `Directorist_Affiliate_Commission` | Resolves commission amounts per event — fixed for registration/listing, fixed **or percentage of order total** for plan/featured orders. Returns `null` (= don't record) when the system/event is disabled **or the required extension is inactive** (`is_pricing_plans_active()`, `is_featured_monetization_active()`). Also owns the auto-approve default status. |
| `Directorist_Affiliate_Order_Integration` | Listens to both Directorist order systems, creates commissions on paid plan/featured orders (deduped per order), and reverses them to `cancelled`/`refunded` when the order is refunded, cancelled, failed, or expired. |
| `Directorist_Affiliate_Payout` | `mark_paid()` validates referrals (must belong to the affiliate and be `approved`), sums them, inserts a payout row, and flips referrals to `paid`. Skips zero-amount payouts. |
| `Directorist_Affiliate_Email` | Plain-text `wp_mail` notices: new application → admin; approve/reject decision → affiliate; new referral recorded → affiliate. |
| `Directorist_Affiliate_View` | Static template renderer (`output()` prints, `render()` returns a string) used by both admin screens and shortcodes. |
| `Directorist_Affiliate_Autoloader` | Classmap `spl_autoload_register` loader; the map doubles as the plugin's class inventory. |
| `Directorist_Affiliate_Registration` | Application processing shared by every entry point: `process_public()` (honeypot, validation, account creation, pending application) and `process_admin()` (status choice, user reuse). Callers do nonce/capability checks. |
| `Directorist_Affiliate_Ajax` | `admin-ajax.php` endpoints for all four forms (`directorist_affiliate_register` incl. `nopriv`, `…_add_affiliate`, `…_save_settings`, `…_mark_paid`), returning JSON via `wp_send_json_*`. |

## Visit tracking details

- Runs on the front end only, and only when the system is enabled in settings.
- The URL parameter name is configurable (`ref` by default) — e.g. `https://site.com/any-page/?ref=42`.
- The code must belong to an **approved** affiliate; logged-in affiliates visiting their own link are ignored (self-referral guard #1).
- Two cookies are set for `cookie_duration` days (default 30), `HttpOnly`, `secure` when SSL:
  - `directorist_affiliate_ref` → affiliate ID
  - `directorist_affiliate_visit` → visit row ID
- Attribution is configurable: **first click** (default, per PRD — an existing valid credit is never overwritten until the cookie expires) or **last click** (each valid `?ref=` hit overwrites the credit). Every counted hit creates a visit row.

## Conversion → referral creation

`Directorist_Affiliate_Directorist_Integration` listens to:

| Hook | Purpose |
| --- | --- |
| `user_register` + `atbdp_user_registration_completed` | Registration conversions |
| `atbdp_after_created_listing` | Listing conversions when trigger = `submission` |
| `transition_post_status` (→ `publish`, Directorist post type only) | Listing conversions when trigger = `publish` |
| `directorist_after_order_create` / `directorist_after_order_update` (Directorist 8.8+ order repository) | Paid-order commissions: `ref_type` `pricing_plan` → `plan_purchase`, `featured_listing`/featured flag → `featured_purchase`; reversal when the order becomes refunded/cancelled/failed/expired |
| `atbdp_order_completed` + `atbdp_order_status_changed` (legacy `atbdp_orders`) | Same for the classic checkout: `_fm_plans` meta → plan, `_featured` meta → featured; admin status changes to cancelled/refunded reverse the commission |
| `directorist_dashboard_tabs` (filter) | Adds an "Affiliate" tab to the Directorist user dashboard |

**Paid order flow:** when an order reaches **paid**, the integration computes the total exactly like core (`sub_total` + tax − coupon, falling back to the stored amount), resolves the affiliate (tracking cookie first, then the affiliate recorded at the buyer's registration), applies the self-referral and per-order duplicate guards, and creates a `plan_purchase`/`featured_purchase` referral storing the order ID, source, and total. Free (0.00) orders never earn. If the order is later refunded or cancelled, the referral flips to `refunded`/`cancelled` and drops out of payable sums; `directorist_affiliate_referral_reversed` fires for extensions.

**Registration flow:** commission amount resolved → affiliate read from cookie → must be approved and not the registering user themselves (self-referral guard #2) → referral created as `pending` → user meta `_directorist_affiliate_id` and `_directorist_affiliate_visit_id` stored on the new user → visit marked converted → affiliate emailed.

**Listing flow:** affiliate resolved from the author's `_directorist_affiliate_id` user meta first, falling back to the live cookie — so a listing posted days after registration (cookie may be gone) still credits the original affiliate. Same approval/self-referral guards, then a `listing_submission` referral is created, the visit marked converted, and the affiliate emailed.

Both paths are idempotent: `Referral::create()` returns the existing row if a referral for the same affiliate + type + user/listing already exists, so double-firing hooks can't double-pay.

## Admin area

One tabbed **Affiliate** page registered as a submenu of the Directorist listings menu (`edit.php?post_type=at_biz_dir`, page slug `directorist-affiliate`, `manage_options`). The shell renders a header (title, version chip, tagline) and a six-tab navigation (`?tab=…`, dashicons, whitelisted via `sanitize_key`); `Directorist_Affiliate_Admin::page_url( $tab, $args )` is the canonical URL builder used by every internal link, redirect, and email. Bookmarks to the old standalone `directorist-affiliate-*` pages 301 to the matching tab on `admin_init`.

| Tab (`?tab=`) | Contents / actions |
| --- | --- |
| `dashboard` | Stat cards: total/pending affiliates, visits, referrals, pending/approved/paid commission totals. |
| `affiliates` | Manual "Add affiliate" form (creates/reuses a WP user, can start as approved), affiliate detail panel, table of affiliates with per-row Approve / Reject / Suspend links (nonce-protected), status badges, and referral/commission rollups. |
| `referrals` | All referrals with Approve / Reject / Mark paid actions and order details. "Mark paid" is **restricted to `approved` referrals** and always routes through the payout service so every payment leaves a payout record; other statuses get an error notice. |
| `visits` | Latest 100 visits: affiliate, landing/referrer URLs, IP, converted badge. |
| `payouts` | Checkbox list of unpaid **approved** referrals → "Mark selected as paid" (grouped into one payout per affiliate, **skipping affiliates below the configured minimum payout** with a warning notice); payout history; **Export approved payouts CSV** (up to 1,000 rows: affiliate_id, payout_email, referral_id, amount, date_created). |
| `settings` | The settings form below, organized into pill-style sub-tabs (General / Registration / Listing / Order Commissions / Payout / Advanced). One form underneath — a single save submits every section (AJAX with POST fallback); JS-off renders the sections stacked. Sub-tab state is kept in the URL hash (`#da-general`). |

Tab rendering lives in `Directorist_Affiliate_Admin`; all mutations live in `Directorist_Affiliate_Admin_Actions`, run through `admin_init`, are capability-checked (`manage_options`) and nonce-verified (`check_admin_referer`), then redirect back to the relevant tab with a success/error notice (or return JSON via the AJAX endpoints).

## Frontend

**Forms are AJAX-first with no-JS fallbacks.** Every form carries a `data-da-ajax` attribute; the shared JS intercepts submit, posts to `admin-ajax.php`, and shows the JSON response inline (invalid input no longer loses what was typed). Without JavaScript, forms fall back to normal POSTs: the front-end registration POST is processed **exactly once on `template_redirect`** (a once-guard prevents the double-processing that occurs when themes/SEO plugins render shortcodes multiple times per request — previously this could report "You already have an affiliate application" on a successful first submit), and admin POSTs are processed on `admin_init` as before. Both paths run the same `Registration`/`Payout` service methods.

**Shortcodes**

- `[directorist_affiliate_registration]` — Application form (name, email, website, promotional channel, payout email, note) with an invisible **honeypot anti-spam field** (bot submissions are silently discarded). For visitors who aren't logged in it **creates a WordPress account** via the shared `Affiliate::register_user()` helper (username derived from the email local-part, random password, standard new-user email). If the email already belongs to an account, it asks them to log in first. One application per user.
- `[directorist_affiliate_dashboard]` — For logged-in affiliates: status badge, visit/referral counts, pending/approved/paid commission totals, their referral URL with a **copy-to-clipboard button** (only shown once approved), payout email, admin-configured payout instructions, and their last 20 referrals.

**Directorist dashboard tab** — The same dashboard renders inside Directorist's user dashboard as an "Affiliate" tab (icon `las la-handshake`) via the `directorist_dashboard_tabs` filter.

Views are rendered with a tiny `ob_start()`/`extract()` template loader; assets are one shared stylesheet and one small vanilla JS file, both registered as `directorist-affiliate` and enqueued on demand.

## Settings reference

Stored in one option, `directorist_affiliate_settings` (autoload off):

| Key | Default | Meaning |
| --- | --- | --- |
| `enabled` | `1` | Master on/off for tracking + commissions |
| `ref_param` | `ref` | Query-string parameter for referral links |
| `cookie_duration` | `30` | Cookie lifetime in days (min 1) |
| `enable_registration` | `1` | Pay commission on referred user registration |
| `registration_amount` | `0.00` | Fixed amount per registration |
| `enable_listing` | `1` | Pay commission on referred listing |
| `listing_amount` | `0.00` | Fixed amount per listing |
| `listing_trigger` | `submission` | `submission` (on create) or `publish` (on first publish) |
| `attribution_model` | `first_click` | `first_click` (existing credit kept until cookie expiry) or `last_click` (newest link wins) |
| `enable_plan_commission` | `1` | Commission on pricing-plan purchases — inert unless a Pricing Plans extension is active |
| `plan_commission_type` / `plan_commission_value` | `percentage` / `0.00` | Fixed amount or % of order total (percentages capped at 100) |
| `enable_featured_commission` | `1` | Commission on featured-listing purchases — inert unless monetization + featured listings are enabled |
| `featured_commission_type` / `featured_commission_value` | `percentage` / `0.00` | Fixed amount or % of order total (percentages capped at 100) |
| `auto_approve_commissions` | `0` | Create referrals as `approved` (payable immediately) instead of `pending` |
| `minimum_payout` | `0.00` | Per-affiliate threshold enforced on the bulk "Mark selected as paid" action (`0` = disabled) |
| `payout_instructions` | `''` | Free text shown on the affiliate dashboard |
| `anonymize_ip` | `0` | Truncate visit IPs via `wp_privacy_anonymize_ip()` at collection time |
| `delete_data_on_uninstall` | `0` | Allow `uninstall.php` to drop tables/options/user meta on plugin delete |

## Status lifecycles

- **Affiliate:** `pending` → `approved` / `rejected` / `suspended` (admin action; approve/reject trigger an email). Only `approved` affiliates get visits and referrals credited.
- **Referral:** `pending` → `approved` (stamps `date_approved`) → `paid` (stamps `date_paid`, normally via a payout record); or `rejected`.
- **Payout:** created directly as `paid` / `manual`.

## Data footprint

| Type | Keys |
| --- | --- |
| Options | `directorist_affiliate_settings`, `directorist_affiliate_db_version` |
| User meta | `_directorist_affiliate_id`, `_directorist_affiliate_visit_id` (on referred users) |
| Cookies | `directorist_affiliate_ref`, `directorist_affiliate_visit` |
| Tables | The four `directorist_affiliate*` tables above |

## Security posture

- Every table access goes through `$wpdb->prepare()`; inputs are sanitized on the way in (`sanitize_email`, `esc_url_raw`, `sanitize_key`, `absint`, …) and escaped on output (`esc_html`, `esc_url`, `esc_attr`).
- All admin actions: `manage_options` + nonces. Frontend application form: nonce-protected POST plus an invisible honeypot field against bot signups.
- Cookies are `HttpOnly` and marked secure on SSL. Statuses/types are validated against whitelists before being written.
- Self-referral is blocked at both the tracking and the referral-creation layers; duplicate referrals are blocked at the repository layer.
- Optional IP anonymization (`wp_privacy_anonymize_ip()`) for visit logs; opt-in full data removal on uninstall.
- Referrals can only be marked paid from the `approved` status, so every payment is backed by a payout record (audit trail).

## Extensibility

Actions: `directorist_affiliate_created( $affiliate_id, $status )`, `directorist_affiliate_status_changed( $affiliate_id, $status )`, `directorist_affiliate_referral_created( $referral_id, $affiliate_id, $type )`, `directorist_affiliate_referral_reversed( $referral_id, $new_status, $order_status )`, `directorist_affiliate_payout_recorded( $payout_id, $affiliate_id, $amount, $referral_ids )`.

Filters: `directorist_affiliate_registration_commission( $amount )`, `directorist_affiliate_listing_commission( $amount, $trigger )`, `directorist_affiliate_plan_commission( $amount, $order_total )`, `directorist_affiliate_featured_commission( $amount, $order_total )`.

Usage examples are in [DOCUMENTATION.md](DOCUMENTATION.md#developer-reference).

## Changelog

### 1.0.0 — 2026-07-28
- **Internationalization complete:** every user-facing string is translatable. Raw database values (referral types and statuses) now render through translated label helpers — `Referral::type_label()`, `Referral::status_label()`, `Affiliate::status_label()` — in admin tables, the front-end dashboard, and emails. Badge CSS classes still derive from raw statuses, so styling is language-independent (`text-transform: capitalize` removed in favor of properly cased labels).
- Added the translation template `languages/directorist-affiliate.pot` (~180 strings, generated with WP-CLI `i18n make-pot`).
- Rebrand: author **wpXplore**, plugin website https://wpxplore.com/tools/directorist-affiliate/, refreshed plugin description.

### 0.5.2 — 2026-07-27
- **Fix:** admin AJAX submissions (Settings, Add Affiliate, bulk payouts) failed with "Something went wrong" — `admin-ajax.php` also fires `admin_init`, so the no-JS fallback handlers intercepted AJAX requests and replied with a redirect instead of JSON. Fallbacks now bail on `wp_doing_ajax()`.

### 0.5.1 — 2026-07-27
- Settings tab organized into pill-style **sub-tabs** (General / Registration Commission / Listing Commission / Order Commissions / Payout / Advanced). Still one form — a single save submits every section via AJAX; sections render stacked without JavaScript; active sub-tab kept in the URL hash.

### 0.5.0 — 2026-07-27
- Admin restructured into a single tabbed **Affiliate** page under the Directorist menu (previously a standalone top-level menu with six subpages). New shell design: header with version chip, dashicon tab navigation, card layout.
- `Directorist_Affiliate_Admin::page_url()` centralizes every admin URL (links, redirects, CSV export, email links); bookmarks to the old `directorist-affiliate-*` page slugs redirect to the matching tab.
- AJAX notices now anchor to `.wp-header-end` (full-width, above the tab bar).

### 0.4.0 — 2026-07-27
- **Revenue-backed commissions (PRD MVP):** commissions on paid orders — pricing-plan purchases (`plan_purchase`) and featured-listing purchases (`featured_purchase`) — as a **fixed amount or percentage of the order total**.
- Integrates both Directorist order systems: the 8.8+ order repository (`directorist_after_order_create/update`, used by Pricing Plans v4 and the featured checkout) and legacy `atbdp_orders` (`atbdp_order_completed`, `atbdp_order_status_changed`).
- **Refund handling:** orders that become refunded/cancelled/failed/expired automatically reverse their referral to the new `refunded`/`cancelled` statuses; `directorist_affiliate_referral_reversed` action added.
- Extension-aware validation: plan commissions require the Pricing Plans extension, featured commissions require monetization + featured listings — settings grey out and runtime guards disable the event when missing.
- Configurable **attribution model**: first click (new default, per PRD) or last click.
- **Auto-approve commissions** setting (applies to all referral types; stamps `date_approved`).
- DB schema 0.2.0: `order_id`, `order_source`, `order_total` columns on referrals (indexed) with automatic `dbDelta` upgrade; per-order commission dedupe; free (0.00) orders never earn.
- New filters `directorist_affiliate_plan_commission` / `directorist_affiliate_featured_commission`; Referrals screen gained an Order column and new status badges.

### 0.3.0 — 2026-07-27
- **All four forms are AJAX-powered** (front-end application incl. logged-out visitors, admin Add Affiliate, Settings, bulk payouts) via `Directorist_Affiliate_Ajax`, with full no-JS fallbacks.
- **Fix:** the front-end application form could process a submission twice (themes/SEO plugins render content multiple times per request), showing "You already have an affiliate application" on a successful first submit. The fallback now processes the POST exactly once on `template_redirect`.
- New `Directorist_Affiliate_Registration` service shares application processing between public/admin/AJAX entry points; bulk payout grouping moved into `Payout::mark_paid_bulk()`.
- Inline success/error notices without losing typed input; per-form success behavior (hide / reload / stay).

### 0.2.0 — 2026-07-27
- Security & privacy: invisible honeypot on the application form, optional visitor-IP anonymization (`wp_privacy_anonymize_ip`), opt-in **uninstall cleanup** (`uninstall.php` + setting).
- Payout integrity: **minimum payout enforced** on bulk payouts (with skip notices); "Mark paid" restricted to approved referrals so every payment leaves a payout record.
- Extensibility: `directorist_affiliate_created`, `_status_changed`, `_referral_created`, `_payout_recorded` actions and registration/listing commission filters.
- Design overhaul: design tokens, stat cards, colored status badges, responsive tables, styled forms, copy-referral-link button (new JS asset).
- Architecture: classmap autoloader (lazy class loading), shared `View` renderer, `Admin`/`Admin_Actions` split, container-managed services; shared `register_user()` helper; real referral `count()` queries.
- Project hygiene: `.gitignore`, technical `README.md`, site-owner `DOCUMENTATION.md`, admin success/error notices.

### 0.1.0
- Initial release: affiliate applications with admin approval, unique referral codes, cookie-based visit tracking, fixed commissions for referred user registrations and listing submissions, self-referral and duplicate guards, manual payouts with CSV export, admin screens (dashboard, affiliates, referrals, visits, payouts, settings), front-end shortcodes, Directorist user-dashboard tab, and email notifications.

## Known limitations / observations

- Payouts are records only — no PayPal/Stripe/etc. integration; money moves outside WordPress (CSV export supports that workflow).
- Claim-listing and ad-package purchases are not yet distinct commission events (the extensions aren't part of this stack); orders that are neither plan nor featured purchases are ignored.
- Recurring/renewal commissions are not implemented (each paid order earns once; subscription renewals that create new paid orders do earn again via order dedupe being per-order).
- Admin tables have fixed query limits (100 rows; 500 approved referrals on the payout screen) and no pagination or search.
- Visit capture happens on `template_redirect`, so full-page caching can prevent cookie setting for cached hits (standard limitation for cookie-based affiliate tracking).
- Visit rows have no retention/cleanup routine; IP anonymization is available but off by default.
- Both listing triggers record the referral as type `listing_submission`; the trigger used is only distinguishable from the referral's notes.
- Attribution is cookie-scoped; conversions after cookie expiry credit no one (order events fall back to the affiliate stored at the buyer's registration, when there was one).
- No custom capabilities — all admin screens require `manage_options`.
