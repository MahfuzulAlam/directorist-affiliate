# Directorist – Affiliate: User Guide

This guide explains how to set up and run an affiliate program on your Directorist directory site. For technical/architecture details, see [README.md](README.md).

## Contents

1. [What this extension does](#what-this-extension-does)
2. [Requirements & installation](#requirements--installation)
3. [Quick start (5 steps)](#quick-start-5-steps)
4. [Settings explained](#settings-explained)
5. [Setting up the affiliate pages](#setting-up-the-affiliate-pages)
6. [The affiliate's experience](#the-affiliates-experience)
7. [Managing affiliates](#managing-affiliates)
8. [Managing referrals](#managing-referrals)
9. [Paying your affiliates](#paying-your-affiliates)
10. [Email notifications](#email-notifications)
11. [Privacy & GDPR](#privacy--gdpr)
12. [Developer reference](#developer-reference)
13. [FAQ & troubleshooting](#faq--troubleshooting)

---

## What this extension does

It adds a complete affiliate/referral program to a Directorist site:

- Visitors **apply to become affiliates** through a form on your site.
- Approved affiliates get a **personal referral link** like `https://yoursite.com/?ref=their-code`.
- When someone follows that link, the visit is tracked with a cookie (30 days by default).
- If that visitor **registers an account** or **submits a listing**, the affiliate earns a **fixed commission** that you define.
- You review referrals, approve the legitimate ones, and **record payouts manually** (bank transfer, PayPal, etc. — whatever you use outside WordPress). A CSV export helps you process payments in bulk.

There is no automatic money transfer — this extension tracks who earned what; you pay them through your own payment channel.

## Requirements & installation

| Requirement | Version |
| --- | --- |
| WordPress | 5.8 or newer |
| PHP | 7.4 or newer |
| Directorist | 8.7.3 or newer (must be active) |

1. Upload the `directorist-affiliate` folder to `wp-content/plugins/` (or install the ZIP via **Plugins → Add New → Upload Plugin**).
2. Activate **Directorist - Affiliate** from the Plugins screen. Activation is blocked if Directorist is not active.
3. On activation the plugin creates its four database tables and default settings automatically — no manual setup needed.

## Quick start (5 steps)

1. **Configure commissions** — Go to **Directorist Affiliate → Settings**, set the fixed amount for registrations and/or listings, and save.
2. **Create an "Become an Affiliate" page** — Add a page containing the shortcode `[directorist_affiliate_registration]`.
3. **Create an "Affiliate Dashboard" page** — Add a page containing `[directorist_affiliate_dashboard]`. (Affiliates can also use the "Affiliate" tab that appears automatically in the Directorist user dashboard.)
4. **Approve your first affiliate** — When someone applies, review them under **Directorist Affiliate → Affiliates** and click **Approve**. They'll be emailed and their referral link becomes active.
5. **Moderate & pay** — Approve incoming referrals under **Referrals**, then record payouts under **Payouts**.

## Settings explained

**Directorist Affiliate → Settings**

### General

| Setting | What it does |
| --- | --- |
| **Enable affiliate system** | Master switch. When off, no visits are tracked and no commissions are recorded. |
| **Referral URL parameter** | The query parameter used in referral links. Default `ref` → links look like `?ref=CODE`. Change it if another plugin already uses `ref`. Existing links with the old parameter stop working after a change. |
| **Cookie duration** | How many days a referral cookie lasts. If a visitor converts within this window, the affiliate is credited. Default 30. |
| **Anonymize visitor IP** | Stores visit IPs with the last octet removed (e.g. `203.0.113.0`). Recommended if you need GDPR-friendly logging. |

### Registration Commission

| Setting | What it does |
| --- | --- |
| **Enable registration commission** | Pay affiliates when a referred visitor creates an account. |
| **Fixed commission amount** | Flat amount per referred registration (e.g. `2.00`). |

### Listing Commission

| Setting | What it does |
| --- | --- |
| **Enable listing commission** | Pay affiliates when a referred user adds a listing. |
| **Fixed commission amount** | Flat amount per referred listing. |
| **Commission trigger** | **On listing submission** credits the affiliate as soon as the listing is created (even if it awaits review). **On listing approval/publish** credits only when the listing is first published — safer against spam submissions. |

### Payout

| Setting | What it does |
| --- | --- |
| **Minimum payout amount** | Per-affiliate threshold for bulk payouts. On the Payouts screen, an affiliate whose selected referrals total less than this is skipped (you'll see a notice). Set `0` to disable. |
| **Payout instructions** | Free text shown to affiliates on their dashboard — e.g. "Payouts are sent via PayPal on the 1st of each month. Minimum $25." |

### Advanced

| Setting | What it does |
| --- | --- |
| **Delete data on uninstall** | When enabled, deleting the plugin removes all affiliate tables, settings, and related user meta. Leave off to keep data through reinstalls. |

## Setting up the affiliate pages

### Application form

Create a page (e.g. "Become an Affiliate") and add:

```
[directorist_affiliate_registration]
```

The form asks for name, email, website, promotional channel, payout email, and an optional note.

- **Logged-out visitors**: a WordPress account is created for them automatically (they receive the standard set-password email). If their email already has an account, they're asked to log in first.
- **Logged-in users**: the form pre-fills their name/email; submitting attaches the application to their account.
- Each user can apply once. Applications start as **Pending**.
- The form includes an invisible anti-spam honeypot; bot submissions are silently discarded.

### Affiliate dashboard

Create a page (e.g. "Affiliate Area") and add:

```
[directorist_affiliate_dashboard]
```

The same dashboard also appears automatically as an **Affiliate** tab inside the Directorist user dashboard, so this page is optional but gives affiliates a direct URL.

## The affiliate's experience

1. They apply via the form and see "pending review".
2. Once you approve them, they receive an email, and their dashboard shows:
   - Status badge, visit count, referral count, and pending/approved/paid commission totals.
   - Their **referral link** with a one-click **Copy link** button.
   - Your payout instructions and their referral history.
3. They share the link — it can point at any page: `https://yoursite.com/?ref=CODE`, `https://yoursite.com/some-listing/?ref=CODE`, etc.
4. Visits and conversions accumulate automatically. Notes:
   - The **last** affiliate link clicked wins if a visitor follows several.
   - Affiliates can't earn from their own sign-ups or listings (self-referrals are blocked).
   - A listing posted later still credits the affiliate who referred the author's registration, even after the cookie expires.

## Managing affiliates

**Directorist Affiliate → Affiliates**

- **Review applications** — every affiliate row shows status, referral code, totals, and links to full details (website, promotional channel, application note).
- **Approve / Reject / Suspend** — row actions. Approve and Reject send the applicant an email. Suspend quietly stops new visits/referrals from being credited without notifying them.
- **Add affiliate manually** — the "Add Affiliate" form creates an affiliate (and a WordPress user if the email is new) with any starting status, e.g. pre-approved partners.

Affiliate statuses: **Pending** (applied, inactive) → **Approved** (link active, earning) / **Rejected** / **Suspended**.

## Managing referrals

**Directorist Affiliate → Referrals**

Each conversion creates a referral with status **Pending**. For every referral you can:

- **Approve** — the commission becomes payable (it moves to the Payouts screen).
- **Reject** — e.g. fraudulent or refunded conversions. Nothing is owed.
- **Mark paid** — only available for **approved** referrals; it records a payout entry for the single referral.

The **Visits** screen shows raw traffic per affiliate (landing page, referrer, IP, converted or not) — useful for spotting suspicious patterns before approving.

## Paying your affiliates

**Directorist Affiliate → Payouts**

1. The **Unpaid approved commissions** table lists every approved, not-yet-paid referral.
2. Tick the referrals you're paying and click **Mark selected as paid**. Referrals are grouped into one payout record per affiliate. Affiliates whose selected total is under the **minimum payout** are skipped with a notice.
3. Pay them through your actual payment channel (PayPal, bank, etc.) using the payout email shown.
4. **Export approved payouts CSV** downloads the approved-referral list (affiliate, payout email, amount, date) for bulk processing in your payment tool.
5. **Payout history** keeps a permanent record of what was paid, when, and to whom.

## Email notifications

| Email | Recipient | When |
| --- | --- | --- |
| New affiliate application | Site admin (`admin_email`) | Someone submits the application form |
| Application approved / rejected | The affiliate | You approve or reject them |
| New referral recorded | The affiliate | A conversion is credited to them |
| New account details | The new user | An account is auto-created during application |

Emails are plain text via `wp_mail()`. Use an SMTP plugin for reliable delivery.

## Privacy & GDPR

- Visits store IP address and browser user agent. Enable **Anonymize visitor IP** to truncate IPs at collection time.
- Tracking cookies (`directorist_affiliate_ref`, `directorist_affiliate_visit`) identify the referring affiliate, not the visitor's identity — but they are still cookies; mention them in your cookie policy and consent tooling if required in your jurisdiction.
- **Delete data on uninstall** lets you fully remove all collected data when retiring the program.

## Developer reference

### Shortcodes

| Shortcode | Renders |
| --- | --- |
| `[directorist_affiliate_registration]` | Affiliate application form |
| `[directorist_affiliate_dashboard]` | Affiliate dashboard (logged-in users) |

### Actions

| Hook | Fires when | Args |
| --- | --- | --- |
| `directorist_affiliate_created` | An affiliate record is created | `$affiliate_id, $status` |
| `directorist_affiliate_status_changed` | An affiliate's status changes | `$affiliate_id, $status` |
| `directorist_affiliate_referral_created` | A new referral is recorded (not for deduped repeats) | `$referral_id, $affiliate_id, $type` |
| `directorist_affiliate_payout_recorded` | A payout is recorded | `$payout_id, $affiliate_id, $amount, $referral_ids` |

### Filters

| Hook | Filters | Args |
| --- | --- | --- |
| `directorist_affiliate_registration_commission` | Commission for a referred registration | `$amount` |
| `directorist_affiliate_listing_commission` | Commission for a referred listing | `$amount, $trigger` |

Example — double listing commissions during a promotion:

```php
add_filter( 'directorist_affiliate_listing_commission', function ( $amount, $trigger ) {
	return $amount * 2;
}, 10, 2 );
```

### Database tables

`{prefix}directorist_affiliates`, `{prefix}directorist_affiliate_visits`, `{prefix}directorist_affiliate_referrals`, `{prefix}directorist_affiliate_payouts`. Full schema in [README.md](README.md).

## FAQ & troubleshooting

**Referral links don't set the cookie.**
Full-page caching (or a CDN serving cached HTML) can prevent the visit capture from running. Exclude URLs containing your referral parameter (`ref` by default) from caching, or configure your cache to vary on it.

**An affiliate's link shows visits but no referrals.**
Commissions are only recorded while the relevant commission type is enabled and the affiliate is **approved** at conversion time. Also check the conversion happened within the cookie duration, and that the visitor isn't the affiliate themselves.

**Can the same registration or listing be credited twice?**
No — the plugin stores one referral per affiliate + conversion (duplicate events are ignored).

**Can I use percentage commissions or tie commissions to paid plans?**
Not out of the box — commissions are flat amounts. The `directorist_affiliate_*_commission` filters let developers compute dynamic amounts.

**How do I change what "counts" as a listing conversion?**
Use the **Commission trigger** setting: `submission` counts every created listing; `publish` waits until the listing goes live (recommended when listings are moderated).

**Where do I see why an affiliate was skipped during a bulk payout?**
The Payouts screen shows a warning notice with the number of skipped affiliates and the configured minimum. Select more of their approved referrals (or lower the minimum) and retry.

**Does deactivating the plugin delete my data?**
No. Even uninstalling keeps all data unless **Delete data on uninstall** is enabled in Settings → Advanced.
