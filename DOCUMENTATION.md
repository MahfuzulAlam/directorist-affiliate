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
10. [Translating the plugin](#translating-the-plugin)
11. [Privacy & GDPR](#privacy--gdpr)
12. [Developer reference](#developer-reference)
13. [FAQ & troubleshooting](#faq--troubleshooting)

---

## How the program works

![Diagram: visitor clicks referral link, registers or submits a listing, admin approves and pays](docs/images/flow-overview.png)
<!-- 📸 IMAGE PLACEHOLDER: simple 5-step flow graphic — Referral link → Tracked visit → Sign-up / listing → Admin approval → Payout -->

1. Someone **applies** to be an affiliate on your site.
2. You **approve** them — they get a personal referral link like `https://yoursite.com/?ref=their-code`.
3. Visitors who follow that link are **tracked with a cookie** (30 days by default, counted from their first click — returning later does not extend it).
4. When a tracked visitor **registers**, **submits a listing**, or **pays for a pricing plan or featured listing**, the affiliate earns a commission — a fixed amount, or a percentage of the order total for paid purchases. Refunded or cancelled orders take their commission back automatically.
5. You **approve the referral** and **record the payout**. Affiliates tell you how they want the money — **PayPal**, **Bank transfer** or **Cash** — and the plugin keeps the books, shows you the details to pay against, and gives you a CSV for batch payments. The money itself still moves through your own channel.

> 💡 **Tip:** Commissions are flat amounts you control. Nothing is ever paid automatically — you always review first.

---

## Install & activate

| Requirement | Version |
| --- | --- |
| WordPress | 6.3+ |
| PHP | 7.4+ |
| Directorist | 8.7.3+ (must be active) |

1. Go to **Plugins → Add New → Upload Plugin**, choose the `directorist-affiliate` ZIP, and click **Install Now** — or copy the folder into `wp-content/plugins/`.
2. Click **Activate**. If Directorist isn't active you'll be stopped with a clear message — activate Directorist first.
3. Done. The plugin creates its database tables and defaults silently; a new **Affiliate** page appears under the **Directorist** menu in your admin sidebar.

![Screenshot: Plugins screen with Directorist – Affiliate activated](docs/images/install-plugins-screen.png)
<!-- 📸 IMAGE PLACEHOLDER: wp-admin Plugins list showing "Directorist - Affiliate" active, with the new sidebar menu visible on the left -->

---

## Quick start — first affiliate in 5 steps

**Step 1 — Set your commission amounts.**
Go to **Directorist → Affiliate → Settings**. Enter a fixed amount for registrations and/or listings (e.g. `2.00` and `5.00`), then click **Save settings**. You'll see a green *"Settings saved."* confirmation without the page reloading.

![Screenshot: Settings screen with commission amounts filled in](docs/images/quickstart-settings.png)
<!-- 📸 IMAGE PLACEHOLDER: Settings page, Registration + Listing commission sections filled, success notice visible -->

**Step 2 — Publish a "Become an Affiliate" page.**
Create a page and add the shortcode `[directorist_affiliate_registration]`.

**Step 3 — Publish an "Affiliate Area" page.**
Create a second page with `[directorist_affiliate_dashboard]`, then select it under **Settings → General → Affiliate dashboard page**. Two things follow from that: affiliates who revisit your application page are sent straight to their dashboard, and the "already applied" message can link there. (The same dashboard also appears automatically as an **Affiliate** tab in the Directorist user dashboard, so the page is optional — but naming it makes the redirect land somewhere you chose.)

**Step 4 — Approve your first applicant.**
When someone applies, open **Directorist → Affiliate → Affiliates**, review their row, and click **Approve**. Their status badge flips to green and they receive an email — their referral link is now live.

**Step 5 — Watch referrals arrive, then pay.**
Approve incoming rows on the **Referrals** screen, then batch-pay them on the **Payouts** screen.

> 💡 **Tip:** Want to test the loop yourself? Open your site in a private browser window with `?ref=CODE`, register a test account, and watch the visit + referral appear in the admin.

---

## A tour of the admin screens

Everything lives in one modern, tabbed page: **Directorist → Affiliate** in the admin sidebar. Six tabs — **Dashboard, Affiliates, Referrals, Visits, Payouts, Settings** — sit under a single header, so you never lose your place, and every form on the page saves via AJAX with inline confirmations.

![Screenshot: the tabbed Affiliate page header and navigation](docs/images/admin-tabbed-shell.png)
<!-- 📸 IMAGE PLACEHOLDER: the Affiliate page under the Directorist menu — header with version chip, the six-tab navigation with Dashboard active -->

Old bookmarks to the previous standalone pages redirect automatically to the right tab.

### Dashboard — your program at a glance

![Screenshot: admin dashboard stat cards](docs/images/admin-dashboard.png)
<!-- 📸 IMAGE PLACEHOLDER: Dashboard screen showing both card rows (Affiliates & traffic / Commissions) with non-zero numbers -->

Two rows of stat tiles plus a recent-activity table:

- **Top row** — affiliates (with a one-click link to your pending applications), link visits and the share of them that converted, and total referrals.
- **Commissions** — pending (awaiting your review), approved (owed, unpaid, linking straight to Payouts), and paid totals, all in your site's currency.
- **Recent referrals** — the last eight conversions, so you can see activity without leaving the tab.

> 💡 **Tip:** A growing *applications awaiting review* or *Pending* commission number is your to-do list — check it a couple of times a week. The header also shows whether the program is currently **active** or **disabled**.

### Affiliates — your partner roster

![Screenshot: Affiliates screen with status badges and row actions](docs/images/admin-affiliates.png)
<!-- 📸 IMAGE PLACEHOLDER: Affiliates table with a mix of Pending (amber), Approved (green), Rejected (red) badges; Add Affiliate panel visible above -->

- **Add affiliate** (first button on the filter row) — opens a popup with name, email, payout email, starting status (you can pre-approve trusted partners), website, and promotional channel. Press Escape, click outside, or hit Cancel to dismiss it; anything you need to fix is shown inside the popup, so nothing you typed is lost.
- **Filter and search** — narrow by status, by when they applied, or search names, emails, referral codes and websites. The count on the right tells you how many matched.
- **The table** — each row shows a colored status badge (**amber** pending, **green** approved, **red** rejected, **gray** suspended), the referral code, referral count (click through to that affiliate's referrals), total earned, and unpaid balance. Twenty per page.
- **Row actions** — **Details** (the full application: website, promotional channel, note), **Approve**, **Reject**, **Suspend**. The action matching an affiliate's current status is hidden, so you can't approve someone twice.

> ⚠️ **Note:** Approve and Reject email the applicant. Suspend is silent — tracking simply stops counting for them.

### Referrals — the money queue

![Screenshot: Referrals screen with pending and approved rows](docs/images/admin-referrals.png)
<!-- 📸 IMAGE PLACEHOLDER: Referrals table showing both referral types, status badges, and the Approve | Reject | Mark paid actions -->

Every conversion lands here as a **Pending** row showing who earned it, the type (*user registration* or *listing submission*), the referred user, the listing (linked to its edit screen), and the amount.

- **Approve** — the commission becomes payable and moves to the Payouts screen.
- **Reject** — for fraud, refunds, or test data. Nothing is owed.
- **Mark paid** — pays a single referral on the spot. Only shown on **approved** rows (this keeps your payout history complete). Once a referral is paid it can't be moved back to pending or approved, so you can never accidentally pay it twice; a refund or cancellation of the underlying order still reverses it automatically.
- **Bulk actions** — tick several rows (or the header checkbox to select the page) and approve, reject, or mark them paid in one go.
- **Filters** — narrow by affiliate, event type (registration, listing, plan purchase, featured purchase), status, and date range. Twenty per page, and your filters stay applied as you page through.

Order-based referrals (plan and featured purchases) also show the **order number and order total**, and carry two automatic statuses: **Cancelled** and **Refunded** are applied by the plugin when the underlying order is cancelled or refunded — nothing for you to do, and those amounts never reach the Payouts screen.

### Visits — the traffic log

![Screenshot: Visits screen](docs/images/admin-visits.png)
<!-- 📸 IMAGE PLACEHOLDER: Visits table with landing pages, referrer URLs, IPs, and green "Yes" converted badges on a few rows -->

The click log per affiliate: the landing page they arrived on, the site they came from, IP, date and time, and a green **Converted** badge once a visit leads to a referral. The same person reloading a referral link is counted **once per day**, not once per refresh, so these numbers reflect real traffic rather than browser behavior. Filter by affiliate, by whether the visit converted, and by date range — useful for questions like *"how much traffic did Sam send last month, and how much of it converted?"* Scan it before approving big referral batches — dozens of visits from one IP is a red flag. Obvious bot and crawler traffic is filtered out automatically, so these numbers reflect real people. Twenty per page.

### Payouts — pay day

![Screenshot: Payouts screen with selected referrals and payout history](docs/images/admin-payouts.png)
<!-- 📸 IMAGE PLACEHOLDER: Payouts screen — some checkboxes ticked in "Unpaid approved commissions", the minimum-payout hint line, and the Payout history table below -->

This screen has three sub-tabs.

**Requests** — where affiliates' payout claims land. You see who is asking, how much, **how to pay them** (the method and its details — PayPal email, bank account, or phone for cash), how many commissions it covers, and any note they left. **Mark paid** settles it: the covered commissions become Paid and the affiliate is emailed. **Reject** declines it and leaves those commissions in their balance, so they can ask again later. If any requests are open, this is the tab you land on, and the tab label shows how many.

> 💡 The method and details are recorded **on the payout itself**, so if an affiliate later changes their bank account, older payments still show where the money actually went.

> 💡 If a commission gets refunded between the request and your payment, it is dropped automatically — you pay only what is still legitimately owed, and the recorded amount is corrected to match. If nothing is left payable, reject the request instead.

**Unpaid approved commissions** — your pay-day work queue, for paying affiliates without waiting for them to ask:

1. **Outstanding balance and Export CSV** (top bar) — see exactly what you owe right now, and download every approved, unpaid referral with the affiliate's payout email, ready for your bank or PayPal batch tool.
2. **The list** — tick the referrals you're paying (or use the header checkbox to select all) and click **Mark selected as paid**. The plugin groups them into **one payout record per affiliate** and confirms inline (e.g. *"2 payouts recorded. 1 affiliate was skipped for being below the minimum payout."*), then refreshes.

**Payout history** — the permanent ledger of settled payouts (paid and rejected; open requests stay on the Requests tab): who was paid, how much, when, to which email, and how many commissions each payment covered. Filter by affiliate, payout email, or date range, and the **Total paid** figure at the top updates to match your filters — handy for month-end reconciliation or answering *"how much did we pay Sam last quarter?"* Twenty per page.

> 💡 **Tip:** Set a **Minimum payout amount** in Settings and the screen enforces it for you — affiliates under the threshold are skipped with a clear warning, never silently.

### Settings

Covered in full in the next section.

---

## Settings, section by section

**Directorist → Affiliate → Settings.** The sections are organized into pill-style sub-tabs — **General, Commissions, Payout, Notifications, Advanced** — so you only see one group at a time. It's still a single form underneath: one **Save settings** click (in the save bar that stays with you as you scroll) stores everything from every sub-tab at once, without a page reload, confirming with *"Settings saved."* If you try to leave with unsaved changes, your browser asks first.

![Screenshot: full settings screen](docs/images/settings-full.png)
<!-- 📸 IMAGE PLACEHOLDER: entire Settings page scrolled to show all five sections -->

### General

| Setting | What it does | UX effect |
| --- | --- | --- |
| **Affiliate system** | Master switch | Off = no tracking, no commissions, links do nothing |
| **Referral URL parameter** | The `?ref=` part of links (default `ref`) | Changing it breaks previously shared links — pick once, early |
| **Cookie duration** | Days a referral is remembered (default 30) | Longer = more generous attribution window |
| **Attribution model** | **First click** (default) or **Last click** | First click: the first affiliate keeps the credit until the cookie expires — referrals can't be "stolen". Last click: the newest link wins |
| **Accept applications** | Whether the application form takes new submissions | Off = the form shows a friendly "applications are closed" notice; existing affiliates keep earning |
| **Affiliate dashboard page** | The page you put `[directorist_affiliate_dashboard]` on | Anyone who has already applied and lands on your application page is sent straight here, instead of seeing a form they can't use. Leave it unset and they go to the Directorist user dashboard instead |
| **Require login to apply** | Applicants must already have an account | Off (default) = applying creates a WordPress account automatically. Turn **on** if you don't want your application page creating accounts |
| **Anonymize visitor IP** | Strips the last IP octet in the Visits log | Turn on for GDPR-friendly logging |

### Commissions

Two cards. **Free events** are flat rewards for conversions where no money changes hands:

- **User registration** — a flat amount when a referred visitor creates an account. Two extra controls sit under it:
  - **Credit the commission** — *on registration*, or *on email verification*, which holds the commission until the person actually confirms their address. Verification is the safer choice if throwaway signups are a concern. If you have Directorist's email verification switched off, this setting has no effect and commissions credit on registration; the screen tells you when it detects that.
  - **Pay for these user types** — Directorist asks new users whether they are signing up to post listings (**Author**) or just to browse (**User**). Untick a type to stop paying for it. Leaving both unticked keeps both paid, rather than silently stopping every registration commission.
- **Listing submission** — a flat amount when a referred user adds a listing, plus the **trigger**: credit *on submission* (the moment it's created, even awaiting moderation) or *on approval/publish* (only when it goes live). **Publish is recommended** if you moderate listings; it keeps spam submissions from earning anything. If you run more than one directory, **Pay for these directory types** appears too — untick a directory to stop paying for listings in it. Leave every box ticked to pay for all of them, including directories you add later.

**Paid orders** are the revenue events — commissions on money actually paid on your site. Each can be a **fixed amount** or a **percentage of the order total**, and each is automatically inactive (greyed out with an explanation) when the feature it depends on isn't available:

| Setting | What it does | Availability |
| --- | --- | --- |
| **Pricing plan purchases** | Commission when a referred user buys a plan/subscription | Needs the **Directorist Pricing Plans** extension active |
| **Featured listing purchases** | Commission when a referred user pays for featured status | Needs Directorist **monetization + featured listings** enabled |
| **Auto-approve commissions** | New referrals start as **Approved** (payable immediately) instead of **Pending** | Always available; leave off to vet every commission |

Good to know: free (0.00) orders never earn, one order can never be credited twice, and if a paid order is later **refunded or cancelled** its commission automatically flips to Refunded/Cancelled and disappears from payouts.

> 💡 **Tip:** A common setup is *10–20% of plan purchases* — choose "Percentage of order total" and enter `15.00` for 15%.

### Payout

- **Minimum payout amount** — per-affiliate threshold enforced on bulk payouts (`0` disables it).
- **Payout instructions** — free text shown on every affiliate's dashboard. Tell them how and when you pay, e.g. *"PayPal, 1st of each month, $25 minimum."*

### Notifications

Five independent switches, all on by default, covering seven emails:

- **New application (to admin)** — you get an email whenever someone applies.
- **Application decision (to affiliate)** — the applicant is emailed when you approve or reject them (two templates, one switch).
- **New referral (to affiliate)** — the affiliate is emailed each time one of their referrals converts. Turn this off if your affiliates are high-volume and would rather check the dashboard.
- **Payout requested (to admin)** — you are told when an affiliate asks to be paid.
- **Payout decision (to affiliate)** — the affiliate hears back when you mark a request paid or decline it (two templates, one switch).

#### Editing the wording

Every email has an **Edit** button that opens its own editor. Change the subject, the body, or both, then save the settings page as usual.

Each editor lists the **placeholder tokens** that email accepts — type them in braces and they are replaced when the mail is sent:

| Token | Becomes |
| --- | --- |
| `{site_name}` | Your site title |
| `{site_url}` | Your home page URL |
| `{affiliate_name}` | The affiliate's name |
| `{affiliate_email}` | The affiliate's email address |
| `{amount}` | The commission or payout amount, in your Directorist currency |
| `{referral_type}` | What earned the commission, e.g. *Listing submission* |
| `{payout_method}` | PayPal, Bank transfer or Cash |
| `{dashboard_url}` | Link to the affiliate dashboard page |
| `{admin_url}` | Link to the matching admin screen |

A token an email does not list is left alone, so a stray `{amount}` in the application email is harmless rather than blank.

**Leave a field empty to use the default.** The grey text you see in an empty field *is* the default that will be sent. Wording identical to the default is also stored as empty, so opening an editor and closing it again never freezes that email on today's wording — it keeps picking up future improvements, and it keeps being translated from the plugin's language files.

### Advanced

- **Cookie duration** — how long a click keeps earning, **30 days** by default. The window is anchored to the visitor's *first* click, so returning through the link does not quietly extend it.
- **Delete data on uninstall** — off by default. When on, deleting the plugin removes all tables, settings, and related user meta. Leave off if you might reinstall.
- **Shortcodes** — a quick copy reference for the two page shortcodes.

---

## Creating the front-end pages

### The application page

Add `[directorist_affiliate_registration]` to any page:

![Screenshot: front-end application form](docs/images/frontend-registration-form.png)
<!-- 📸 IMAGE PLACEHOLDER: the styled application form on the front end — two-column grid, required asterisks, Apply button -->

What visitors experience:

- **The offer first.** Above the form, a panel shows exactly what they would earn — your live commission rates per event, and how long a referred visitor stays credited to them. It is generated from your settings, so it is never out of date, and events you have switched off (or whose extension is inactive) simply do not appear.
- A form grouped into **About you**, **How you will promote us**, and **Getting paid**, with required fields marked by a red asterisk.
- Submitting happens **instantly, without a page reload** — the button switches to *"Submitting…"*, then either a green success notice replaces the form or a red notice explains what to fix (nothing they typed is lost).
- **Logged-out visitors** get a WordPress account created automatically and receive the standard set-password email. If their email already has an account, they're asked to log in first. Prefer not to have accounts created this way? Turn on **Require login to apply** in Settings → General.
- **Logged-in users** see their name and email pre-filled. If they have already applied, they are taken to their dashboard rather than shown a form they cannot submit — set your dashboard page in Settings so they land where you want.
- One application per person. Bots are filtered by an invisible honeypot, and guest applications are rate-limited per network, so the form can't be used to mass-create accounts.
- When **Accept applications** is off, the page shows a polite "applications are closed" notice instead of the form.

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

**While pending:** a banner explaining that the application is under review and that they will be emailed either way. No referral link is shown yet.

The dashboard is split into five tabs — **Summary** (where they land, showing their earnings), **Your link**, **Referrals**, **Payouts** and **Settings**.

**Once approved:**

- **Their referral link, front and centre.** One section does the whole job: the link with a **Copy** button, controls to point it anywhere on the site, and one-tap sharing to WhatsApp, X, Facebook or email. A plain sentence tells them how long a click stays credited.
- **"Ready to be paid" as the headline figure**, followed by pending, paid-to-date and traffic. If you have set a **minimum payout**, a progress bar shows how close they are and exactly how much more they need — this is the question affiliates ask most often, so it is answered without them having to write to you.
- **Pointing the link somewhere specific** — inside that same section, they pick what they're linking to (**Home page, Page, Post, Listing, Category, Location** or **Custom link**). Home page is selected by default, so the link is ready to share the moment the page loads. Choosing anything else reveals a search box: type part of a title, pick from the list, and the link updates. Custom link lets them paste any address on your site — links to other websites are refused, because a referral can only be tracked on your own domain. **The share buttons always match the link they just built.**
- **Referrals** — their referral history, on its own tab.
- **Settings** (last tab) — where they choose how to be paid, from the methods you allow:
  - **PayPal** — asks for their PayPal email
  - **Bank transfer** — asks for account holder name, bank name, and account number or IBAN (plus an optional routing/SWIFT/BIC for international payments)
  - **Cash** — asks for a phone number so you can arrange the handover

  Saving it once means they never retype it, and the fields change as they switch method.
- **How you get paid** — their referral code, your minimum, your payout instructions, and a **Request payout** button. Clicking it opens a short form showing exactly what they'd be paid. If they have already saved payout details, those are shown with a **Change** link and nothing needs retyping; if not, the form asks how to pay them before it will submit — so **you never receive a request you cannot act on**. Whatever they enter becomes their saved default. If they have no approved balance, are below your minimum, or already have a request waiting, the button is disabled and the reason is shown.

Sharing works on any URL: `?ref=CODE` can be appended to the homepage, a listing, a category — every entry page counts.

> ⚠️ **Note for affiliates:** self-referrals don't count (the plugin blocks them). If a visitor clicks two different affiliate links, whichever wins depends on your **Attribution model** setting — first click by default.

**If suspended or rejected:** a banner explains the situation instead of showing a link, and no new referrals are tracked. Commissions already earned stay visible in their history.

> 💡 **Note:** searches match the **title only** and show at most 10 results, so tell affiliates to search by a word they know is in the name. Only published content appears — drafts and private pages are never offered.

> 💡 **Tip for developers:** the block picks up your theme's font automatically and renders light by default. To match your brand colour, set `--da-accent` on `.directorist-affiliate-wrap` in your theme's CSS. If your theme is dark, add the class `da-dark` to the page body (or the wrapper) and the block switches to a dark palette — it deliberately ignores the visitor's OS dark-mode setting, since that would otherwise put a dark panel on a light page.

---

## Filtering and date ranges

Affiliates, Referrals, Visits, and Payout history all share the same filter bar, and every one of them includes a **date filter**.

Pick a ready-made period — **Today, Yesterday, This week, Last week, This month, Last month, Last 7 days, Last 30 days, This year** — and the list updates immediately. Choose **Custom range…** instead and two date boxes appear for an exact start and end; both ends are included, and if you enter them backwards the plugin sorts it out rather than showing nothing.

A few things worth knowing:

- **Weeks follow your site's setting.** "This week" starts on whichever day you've set as the start of the week in **Settings → General**.
- **Filters survive paging.** Move to page 2 and your filters come with you; the URL is shareable and bookmarkable, so you can save a view like "last month's payouts for Sam".
- **Payout history filters on the payment date**, while the other screens filter on when the record was created — so "Last month" on Payout history means money that went out last month.
- **Reset** clears everything back to the unfiltered list.

---

## Everyday workflows

### Reviewing an application (≈1 minute)

1. **Directorist → Affiliate → Affiliates** — pending rows wear an amber badge.
2. Click **View details** to read their website, promotional channel, and note.
3. Click **Approve** (they're emailed and go live) or **Reject** (they're emailed a decline).

### Moderating referrals (weekly)

1. Open **Referrals**; pending rows are your queue.
2. Cross-check anything unusual against the **Visits** log (same IP repeatedly? empty referrers?).
3. **Approve** the legitimate ones — they queue up on Payouts.

### Running a payout day (monthly)

1. Open **Payouts → Unpaid approved commissions** and click **Export CSV**.
2. Pay the affiliates through your bank/PayPal using the emails in the CSV.
3. Back on the screen, tick the referrals you just paid and click **Mark selected as paid**.
4. The inline confirmation tells you how many payouts were recorded and whether anyone was skipped for the minimum.
5. Switch to **Payout history** and set the date filter to **This month** to confirm the total matches what actually left your account.

![Screenshot: payout confirmation notice with paid and skipped counts](docs/images/workflow-payout-confirmation.png)
<!-- 📸 IMAGE PLACEHOLDER: green "2 payouts recorded." + amber "1 affiliate was skipped…" notices at the top of the Payouts screen -->

---

## Email notifications

| Email | Goes to | Trigger |
| --- | --- | --- |
| New affiliate application | Site admin | Someone applies |
| Application approved / rejected | The affiliate | You decide on their application |
| New referral recorded | The affiliate | A conversion is credited to them |
| Payout requested | Site admin | An affiliate requests a payout |
| Payout paid / declined | The affiliate | You mark their request paid or reject it |
| New account details | The new user | An account was auto-created during application |

All emails are plain text via `wp_mail()`. Every one except the account-details email can be switched off and **rewritten** in **Settings → Notifications** — see [Notifications](#notifications).

> 💡 **Tip:** Pair this with an SMTP plugin (e.g. WP Mail SMTP) so notifications reliably reach inboxes.

---

## Translating the plugin

Everything the plugin says is translatable. There are two kinds of text, and they are translated in two different places.

### Text that ships with the plugin

Buttons, labels, table headings, error messages and the **default** email wording all live in the code under the text domain `directorist-affiliate`, with a ready-made template at `languages/directorist-affiliate.pot`.

**With Loco Translate** (easiest, no files to move):

1. Install and activate *Loco Translate*.
2. Go to **Loco Translate → Plugins → Directorist – Affiliate**.
3. Click **New language**, pick your language, and keep the default location (*System* or *Custom*, not *Author*, so an update cannot overwrite it).
4. Translate and hit **Save** — Loco compiles the `.mo` for you and the site switches over immediately.

**With Poedit:** open `languages/directorist-affiliate.pot`, translate, and save as `directorist-affiliate-{locale}.po` (e.g. `directorist-affiliate-de_DE.po`) into `wp-content/languages/plugins/`.

### Text you typed yourself

Anything you type into the settings screen — your payout instructions, any email subject or body you rewrote — is stored in the database, so it never reaches the `.pot` file and Loco cannot see it. On a single-language site that is fine: you wrote it in the language you wanted.

On a **multilingual site**, WPML and Polylang handle it:

1. Install **WPML String Translation** (or Polylang's string translation).
2. The plugin ships a `wpml-config.xml`, so WPML finds these automatically under **WPML → String Translation → Admin Texts → `directorist_affiliate_settings`**.
3. Email overrides are also registered directly under the string-translation context **Directorist - Affiliate**, named `{template}_subject` and `{template}_body` — for example `affiliate_approved_subject`.

> 💡 **Tip:** If you have *not* rewritten an email, do not translate it here — there will be nothing to find. Untouched emails are translated with the rest of the plugin, in your `.po` file, which is less work and survives being edited later.

---

## Privacy & GDPR

- Visits record IP address and browser user agent. Enable **Anonymize visitor IP** (Settings → General) to truncate IPs at collection time.
- Two cookies (`directorist_affiliate_ref`, `directorist_affiliate_visit`) attribute visits to affiliates. Mention them in your cookie policy/consent tool if your jurisdiction requires it. If you use a consent plugin, a developer can wire it to the `directorist_affiliate_should_track` filter so no affiliate cookie is set until the visitor agrees.
- **Delete data on uninstall** (Settings → Advanced) guarantees a clean exit — tables, options, and user meta are removed when you delete the plugin.

---

## Developer reference

### Shortcodes

| Shortcode | Renders |
| --- | --- |
| `[directorist_affiliate_registration]` | The application form |
| `[directorist_affiliate_dashboard]` | The affiliate dashboard (logged-in users) |
| `[directorist_affiliate_link]` | The current affiliate's referral link to a specific page. Attributes: `page` (`home`, `add-listing`, `all-listings`, `dashboard`, `checkout`), `url` (an explicit same-site URL), `text` (link label). Renders nothing for anyone who isn't an approved affiliate. |

Example — a "promote us" call to action for approved affiliates:

```
[directorist_affiliate_link page="add-listing" text="Share the Add Listing page"]
```

### Actions

| Hook | Fires when | Args |
| --- | --- | --- |
| `directorist_affiliate_created` | An affiliate record is created | `$affiliate_id, $status` |
| `directorist_affiliate_status_changed` | An affiliate's status changes | `$affiliate_id, $status` |
| `directorist_affiliate_referral_created` | A new referral is recorded (not for deduped repeats) | `$referral_id, $affiliate_id, $type` |
| `directorist_affiliate_referral_reversed` | An order refund/cancellation reversed a referral | `$referral_id, $new_status, $order_status` |
| `directorist_affiliate_payout_recorded` | A payout is recorded | `$payout_id, $affiliate_id, $amount, $referral_ids` |

### Filters

| Hook | Filters | Args |
| --- | --- | --- |
| `directorist_affiliate_registration_commission` | Commission for a referred registration | `$amount` |
| `directorist_affiliate_listing_commission` | Commission for a referred listing | `$amount, $trigger` |
| `directorist_affiliate_plan_commission` | Commission for a referred plan purchase | `$amount, $order_total` |
| `directorist_affiliate_featured_commission` | Commission for a referred featured purchase | `$amount, $order_total` |
| `directorist_affiliate_email_templates` | Default email wording, toggles and tokens | `$templates` |
| `directorist_affiliate_email_content` | A rendered subject/body, just before sending | `$email, $key, $tokens` |

Example — double listing commissions during a promotion:

```php
add_filter( 'directorist_affiliate_listing_commission', function ( $amount, $trigger ) {
	return $amount * 2;
}, 10, 2 );
```

Example — append a signature to every affiliate-facing email:

```php
add_filter( 'directorist_affiliate_email_content', function ( $email, $key, $tokens ) {
	if ( 0 === strpos( $key, 'affiliate_' ) ) {
		$email['body'] .= "\n\n--\nThe " . $tokens['site_name'] . ' team';
	}

	return $email;
}, 10, 3 );
```

### AJAX endpoints

All forms post to `admin-ajax.php`; each also has a full non-JavaScript fallback. Bulk referral moderation posts to `admin_init` with the `directorist_affiliate_referral_bulk` nonce.

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
Yes — since 0.4.0, plan and featured-listing purchases support percentage-of-order-total (or fixed) commissions under **Settings → Order Commissions (Paid Events)**. Registration and listing-submission events remain flat amounts; the `directorist_affiliate_*_commission` filters cover anything custom.

**Why is "Pricing plan purchases" greyed out in Settings?**
The Directorist Pricing Plans extension isn't active (for the featured event: monetization/featured listings are off). Activate the required feature and the fields unlock — until then that commission event stays safely disabled, even if it was previously configured.

**Why was an affiliate skipped during bulk payout?**
Their selected referrals total less than your **Minimum payout amount**. The warning notice tells you how many were skipped; select more of their referrals or lower the minimum.

**Does deactivating or uninstalling delete my data?**
Deactivating never does. Uninstalling only does if **Delete data on uninstall** is enabled in Settings → Advanced.
