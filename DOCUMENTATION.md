<!--
  ============================================================
  AUTHOR NOTES (delete this comment block when the doc is final)

  Media conventions used in this file:
  • Image placeholders look like:
      ![Alt text](docs/images/file-name.png)
      <!- 📸 IMAGE PLACEHOLDER: what to capture ->
    Drop the real screenshot into docs/images/ using the same
    file name and the image appears automatically.
  • Recommended screenshot width: 1280px (admin), 900px (front end).
  • The video placeholder at the top links a thumbnail image to a
    video URL — replace both when the walkthrough video is ready.
  ============================================================
-->

# Directorist – Affiliate: User Guide

Turn your visitors into promoters. This guide walks you through every screen of the **Directorist – Affiliate** extension — what you'll see, what to click, and what happens next. For code-level details, see [README.md](README.md).

[![Watch: Set up your affiliate program in 5 minutes](docs/images/video-walkthrough-thumbnail.png)](https://example.com/REPLACE-WITH-VIDEO-URL)
<!-- 🎬 VIDEO PLACEHOLDER: 3–5 min walkthrough — install → settings → create pages → approve an affiliate → first payout. Replace the thumbnail image AND the link URL. -->

---

## Contents

1. [How the program works](#how-the-program-works)
2. [Install & activate](#install--activate)
3. [Quick start — first affiliate in 5 steps](#quick-start--first-affiliate-in-5-steps)
4. [A tour of the admin screens](#a-tour-of-the-admin-screens)
5. [Settings, section by section](#settings-section-by-section)
6. [Creating the front-end pages](#creating-the-front-end-pages)
7. [What your affiliates see](#what-your-affiliates-see)
8. [Everyday workflows](#everyday-workflows)
9. [Email notifications](#email-notifications)
10. [Privacy & GDPR](#privacy--gdpr)
11. [Developer reference](#developer-reference)
12. [FAQ & troubleshooting](#faq--troubleshooting)

---

## How the program works

![Diagram: visitor clicks referral link, registers or submits a listing, admin approves and pays](docs/images/flow-overview.png)
<!-- 📸 IMAGE PLACEHOLDER: simple 5-step flow graphic — Referral link → Tracked visit → Sign-up / listing → Admin approval → Payout -->

1. Someone **applies** to be an affiliate on your site.
2. You **approve** them — they get a personal referral link like `https://yoursite.com/?ref=their-code`.
3. Visitors who follow that link are **tracked with a cookie** (30 days by default).
4. When a tracked visitor **registers** or **submits a listing**, the affiliate earns a **fixed commission**.
5. You **approve the referral** and **record the payout**. Money moves through your own channel (PayPal, bank, etc.) — the plugin keeps the books and gives you a CSV for batch payments.

> 💡 **Tip:** Commissions are flat amounts you control. Nothing is ever paid automatically — you always review first.

---

## Install & activate

| Requirement | Version |
| --- | --- |
| WordPress | 5.8+ |
| PHP | 7.4+ |
| Directorist | 8.7.3+ (must be active) |

1. Go to **Plugins → Add New → Upload Plugin**, choose the `directorist-affiliate` ZIP, and click **Install Now** — or copy the folder into `wp-content/plugins/`.
2. Click **Activate**. If Directorist isn't active you'll be stopped with a clear message — activate Directorist first.
3. Done. The plugin creates its database tables and defaults silently; a new **Directorist Affiliate** menu (🔗 network icon) appears in your admin sidebar.

![Screenshot: Plugins screen with Directorist – Affiliate activated](docs/images/install-plugins-screen.png)
<!-- 📸 IMAGE PLACEHOLDER: wp-admin Plugins list showing "Directorist - Affiliate" active, with the new sidebar menu visible on the left -->

---

## Quick start — first affiliate in 5 steps

**Step 1 — Set your commission amounts.**
Go to **Directorist Affiliate → Settings**. Enter a fixed amount for registrations and/or listings (e.g. `2.00` and `5.00`), then click **Save settings**. You'll see a green *"Settings saved."* confirmation without the page reloading.

![Screenshot: Settings screen with commission amounts filled in](docs/images/quickstart-settings.png)
<!-- 📸 IMAGE PLACEHOLDER: Settings page, Registration + Listing commission sections filled, success notice visible -->

**Step 2 — Publish a "Become an Affiliate" page.**
Create a page and add the shortcode `[directorist_affiliate_registration]`.

**Step 3 — Publish an "Affiliate Area" page.**
Create a second page with `[directorist_affiliate_dashboard]`. (The same dashboard also shows up automatically as an **Affiliate** tab in the Directorist user dashboard, so this page is optional but nice to link in menus.)

**Step 4 — Approve your first applicant.**
When someone applies, open **Directorist Affiliate → Affiliates**, review their row, and click **Approve**. Their status badge flips to green and they receive an email — their referral link is now live.

**Step 5 — Watch referrals arrive, then pay.**
Approve incoming rows on the **Referrals** screen, then batch-pay them on the **Payouts** screen.

> 💡 **Tip:** Want to test the loop yourself? Open your site in a private browser window with `?ref=CODE`, register a test account, and watch the visit + referral appear in the admin.

---

## A tour of the admin screens

Everything lives under the **Directorist Affiliate** sidebar menu.

### Dashboard — your program at a glance

![Screenshot: admin dashboard stat cards](docs/images/admin-dashboard.png)
<!-- 📸 IMAGE PLACEHOLDER: Dashboard screen showing both card rows (Affiliates & traffic / Commissions) with non-zero numbers -->

Two rows of stat cards:

- **Affiliates & traffic** — total affiliates, pending applications (your review queue), total tracked visits, total referrals.
- **Commissions** — pending (awaiting your review), approved (owed, unpaid), and paid totals.

> 💡 **Tip:** A growing *Pending affiliates* or *Pending commission* number is your to-do list — check it a couple of times a week.

### Affiliates — your partner roster

![Screenshot: Affiliates screen with status badges and row actions](docs/images/admin-affiliates.png)
<!-- 📸 IMAGE PLACEHOLDER: Affiliates table with a mix of Pending (amber), Approved (green), Rejected (red) badges; Add Affiliate panel visible above -->

- **Add Affiliate panel** (top) — onboard a partner yourself: name, email, payout email, starting status (you can pre-approve trusted partners), website, promotional channel. Submitting shows an inline confirmation, then the list refreshes.
- **The table** — each row shows a colored status badge (**amber** pending, **green** approved, **red** rejected, **gray** suspended), the referral code, totals for referrals and commission, and unpaid balance.
- **Row actions** — **View details** (opens the full application: website, promotional channel, note), **Approve**, **Reject**, **Suspend**.

> ⚠️ **Note:** Approve and Reject email the applicant. Suspend is silent — tracking simply stops counting for them.

### Referrals — the money queue

![Screenshot: Referrals screen with pending and approved rows](docs/images/admin-referrals.png)
<!-- 📸 IMAGE PLACEHOLDER: Referrals table showing both referral types, status badges, and the Approve | Reject | Mark paid actions -->

Every conversion lands here as a **Pending** row showing who earned it, the type (*user registration* or *listing submission*), the referred user, the listing (linked to its edit screen), and the amount.

- **Approve** — the commission becomes payable and moves to the Payouts screen.
- **Reject** — for fraud, refunds, or test data. Nothing is owed.
- **Mark paid** — pays a single referral on the spot. Only works on **approved** rows; otherwise you'll see an error notice asking you to approve first (this keeps your payout history complete).

### Visits — the traffic log

![Screenshot: Visits screen](docs/images/admin-visits.png)
<!-- 📸 IMAGE PLACEHOLDER: Visits table with landing pages, referrer URLs, IPs, and green "Yes" converted badges on a few rows -->

The raw click log per affiliate: landing page, where the visitor came from, IP, date, and a green **Yes** badge once a visit converts. Scan it before approving big referral batches — dozens of visits from one IP is a red flag.

### Payouts — pay day

![Screenshot: Payouts screen with selected referrals and payout history](docs/images/admin-payouts.png)
<!-- 📸 IMAGE PLACEHOLDER: Payouts screen — some checkboxes ticked in "Unpaid approved commissions", the minimum-payout hint line, and the Payout history table below -->

Three things on one screen:

1. **Export approved payouts CSV** (top button) — downloads every approved, unpaid referral with the affiliate's payout email, ready for your bank or PayPal batch tool.
2. **Unpaid approved commissions** — tick the referrals you're paying and click **Mark selected as paid**. The plugin groups them into **one payout record per affiliate** and confirms inline (e.g. *"2 payouts recorded. 1 affiliate was skipped for being below the minimum payout."*), then refreshes.
3. **Payout history** — a permanent ledger: who was paid, how much, when, and to which email.

> 💡 **Tip:** Set a **Minimum payout amount** in Settings and the screen enforces it for you — affiliates under the threshold are skipped with a clear warning, never silently.

### Settings

Covered in full in the next section.

---

## Settings, section by section

**Directorist Affiliate → Settings.** The form saves without a page reload and confirms with *"Settings saved."*

![Screenshot: full settings screen](docs/images/settings-full.png)
<!-- 📸 IMAGE PLACEHOLDER: entire Settings page scrolled to show all five sections -->

### General

| Setting | What it does | UX effect |
| --- | --- | --- |
| **Enable affiliate system** | Master switch | Off = no tracking, no commissions, links do nothing |
| **Referral URL parameter** | The `?ref=` part of links (default `ref`) | Changing it breaks previously shared links — pick once, early |
| **Cookie duration** | Days a referral is remembered (default 30) | Longer = more generous attribution window |
| **Anonymize visitor IP** | Strips the last IP octet in the Visits log | Turn on for GDPR-friendly logging |

### Registration Commission

Enable/disable, plus the flat amount paid when a referred visitor creates an account.

### Listing Commission

Enable/disable, the flat amount, and the **Commission trigger**:

- **On listing submission** — credit the moment a listing is created (even if it awaits moderation).
- **On listing approval/publish** — credit only when it goes live. **Recommended** if you moderate listings; it keeps spam submissions from earning anything.

### Payout

- **Minimum payout amount** — per-affiliate threshold enforced on bulk payouts (`0` disables it).
- **Payout instructions** — free text shown on every affiliate's dashboard. Tell them how and when you pay, e.g. *"PayPal, 1st of each month, $25 minimum."*

### Advanced

- **Delete data on uninstall** — off by default. When on, deleting the plugin removes all tables, settings, and related user meta. Leave off if you might reinstall.

---

## Creating the front-end pages

### The application page

Add `[directorist_affiliate_registration]` to any page:

![Screenshot: front-end application form](docs/images/frontend-registration-form.png)
<!-- 📸 IMAGE PLACEHOLDER: the styled application form on the front end — two-column grid, required asterisks, Apply button -->

What visitors experience:

- A clean two-column form: **Name**, **Email**, **Website**, **Promotional channel**, **Payout email**, and an optional note. Required fields are marked with a red asterisk.
- Submitting happens **instantly, without a page reload** — the button switches to *"Submitting…"*, then either a green success notice replaces the form or a red notice explains what to fix (nothing they typed is lost).
- **Logged-out visitors** get a WordPress account created automatically and receive the standard set-password email. If their email already has an account, they're asked to log in first.
- **Logged-in users** see their name and email pre-filled.
- One application per person; bots are filtered by an invisible honeypot.

![Screenshot: success message after applying](docs/images/frontend-registration-success.png)
<!-- 📸 IMAGE PLACEHOLDER: the green "Your affiliate application was submitted and is pending review." notice shown in place of the form -->

### The affiliate dashboard page

Add `[directorist_affiliate_dashboard]` to a page — and/or rely on the **Affiliate** tab that appears automatically inside the Directorist user dashboard:

![Screenshot: Affiliate tab inside the Directorist user dashboard](docs/images/frontend-dashboard-tab.png)
<!-- 📸 IMAGE PLACEHOLDER: Directorist user dashboard with the "Affiliate" tab (handshake icon) selected -->

---

## What your affiliates see

![Screenshot: affiliate dashboard with stats, referral link and copy button](docs/images/frontend-affiliate-dashboard.png)
<!-- 📸 IMAGE PLACEHOLDER: approved affiliate's dashboard — stat cards, referral link field with "Copy link" button, referral history table -->

**While pending:** their stat cards plus a notice — *"Your application is being reviewed. Your referral link will appear here once you are approved."* No link is shown yet.

**Once approved:**

- **Stat cards** — status badge, visits, referrals, and pending / approved / paid commission totals.
- **Referral link** with a one-click **Copy link** button (it flashes *"Copied!"* in green). Clicking the field also selects the whole URL.
- **Your payout instructions** and their payout email.
- **Referral history** — their last 20 referrals with amount, status badge, and date.

Sharing works on any URL: `?ref=CODE` can be appended to the homepage, a listing, a category — every entry page counts.

> ⚠️ **Note for affiliates:** self-referrals don't count (the plugin blocks them), and if a visitor clicks two different affiliate links, the **last** click wins.

---

## Everyday workflows

### Reviewing an application (≈1 minute)

1. **Directorist Affiliate → Affiliates** — pending rows wear an amber badge.
2. Click **View details** to read their website, promotional channel, and note.
3. Click **Approve** (they're emailed and go live) or **Reject** (they're emailed a decline).

### Moderating referrals (weekly)

1. Open **Referrals**; pending rows are your queue.
2. Cross-check anything unusual against the **Visits** log (same IP repeatedly? empty referrers?).
3. **Approve** the legitimate ones — they queue up on Payouts.

### Running a payout day (monthly)

1. Open **Payouts** and click **Export approved payouts CSV**.
2. Pay the affiliates through your bank/PayPal using the emails in the CSV.
3. Back on the screen, tick the referrals you just paid and click **Mark selected as paid**.
4. The inline confirmation tells you how many payouts were recorded and whether anyone was skipped for the minimum. The **Payout history** below is your audit trail.

![Screenshot: payout confirmation notice with paid and skipped counts](docs/images/workflow-payout-confirmation.png)
<!-- 📸 IMAGE PLACEHOLDER: green "2 payouts recorded." + amber "1 affiliate was skipped…" notices at the top of the Payouts screen -->

---

## Email notifications

| Email | Goes to | Trigger |
| --- | --- | --- |
| New affiliate application | Site admin | Someone applies |
| Application approved / rejected | The affiliate | You decide on their application |
| New referral recorded | The affiliate | A conversion is credited to them |
| New account details | The new user | An account was auto-created during application |

All emails are plain text via `wp_mail()`.

> 💡 **Tip:** Pair this with an SMTP plugin (e.g. WP Mail SMTP) so notifications reliably reach inboxes.

---

## Privacy & GDPR

- Visits record IP address and browser user agent. Enable **Anonymize visitor IP** (Settings → General) to truncate IPs at collection time.
- Two cookies (`directorist_affiliate_ref`, `directorist_affiliate_visit`) attribute visits to affiliates. Mention them in your cookie policy/consent tool if your jurisdiction requires it.
- **Delete data on uninstall** (Settings → Advanced) guarantees a clean exit — tables, options, and user meta are removed when you delete the plugin.

---

## Developer reference

### Shortcodes

| Shortcode | Renders |
| --- | --- |
| `[directorist_affiliate_registration]` | The application form |
| `[directorist_affiliate_dashboard]` | The affiliate dashboard (logged-in users) |

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

### AJAX endpoints

All four forms post to `admin-ajax.php`; each also has a full non-JavaScript fallback.

| Action | Access | Nonce action |
| --- | --- | --- |
| `directorist_affiliate_register` | Public (`nopriv` + logged-in) | `directorist_affiliate_register` |
| `directorist_affiliate_add_affiliate` | `manage_options` | `directorist_affiliate_add_affiliate` |
| `directorist_affiliate_save_settings` | `manage_options` | `directorist_affiliate_save_settings` |
| `directorist_affiliate_mark_paid` | `manage_options` | `directorist_affiliate_mark_paid` |

### Database tables

`{prefix}directorist_affiliates`, `{prefix}directorist_affiliate_visits`, `{prefix}directorist_affiliate_referrals`, `{prefix}directorist_affiliate_payouts` — full schema in [README.md](README.md).

---

## FAQ & troubleshooting

**The application form says "You already have an affiliate application" on the very first submit.**
That was a bug before 0.3.0 (page content rendered twice could process the form twice). Since 0.3.0 submissions are processed exactly once and normally via AJAX — update the plugin if you still see it.

**The form button says "Submitting…" but nothing happens.**
Open the browser console (F12) — a red line usually names the blocker. Common causes: a security plugin blocking `admin-ajax.php` for visitors, or a JavaScript error from another plugin. The form still works with JavaScript disabled, which is a quick way to confirm where the problem lives.

**Referral links don't set the cookie.**
Full-page caching or a CDN can serve cached HTML that skips tracking. Exclude URLs containing your referral parameter (`ref` by default) from cache, or configure the cache to vary on it.

**Visits are counted but no referrals appear.**
Check that: the affiliate was **approved** at conversion time, the relevant commission type is enabled, the conversion happened within the cookie window, and the visitor isn't the affiliate themselves.

**Can the same sign-up or listing be credited twice?**
No — one referral per affiliate per conversion, enforced at the database layer. Duplicate events are ignored.

**Can I pay percentages, or hook into paid plans?**
Not out of the box — commissions are flat. Developers can compute dynamic amounts with the `directorist_affiliate_*_commission` filters above.

**Why was an affiliate skipped during bulk payout?**
Their selected referrals total less than your **Minimum payout amount**. The warning notice tells you how many were skipped; select more of their referrals or lower the minimum.

**Does deactivating or uninstalling delete my data?**
Deactivating never does. Uninstalling only does if **Delete data on uninstall** is enabled in Settings → Advanced.
