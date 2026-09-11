# Yelling Pages 🖕

> *Let your finger do the talking.*

A parody of the Yellow Pages and a spiritual successor to *Fucked Company*. People
email workplace rants to **rant@yellingpages.com**; the rant is ingested, real
names / companies / contact info are **scrubbed and swapped for funny aliases**,
a moderator approves it, and then it's posted for everyone to read, comment on,
bookmark, and share.

Built with **Laravel 13 + Inertia 2 + React 19 (TypeScript) + Tailwind 4** and
**MySQL**. Auth is Laravel Fortify (from the React starter kit).

## Getting started

```bash
# Already scaffolded. From the project root:
cp .env.example .env          # (already done) — uses MySQL "yellingpages" on 127.0.0.1
php artisan key:generate      # (already done)
php artisan migrate:fresh --seed
composer run dev              # serves app + queue worker + Vite together
```

The site is served by Herd at **http://yellingpages.test**.

### Demo accounts (password: `password`)

| Role  | Email                   |
|-------|-------------------------|
| Admin | `admin@yellingpages.test` |
| User  | `demo@yellingpages.test`  |

## How a rant becomes a post

A rant can arrive three ways — all land as **pending** and queue a scrub job;
nothing is public until a moderator approves it.

1. **Web form** — `/rants/create` (auth + verified, rate-limited, honeypot + terms).
2. **Email webhook** — `POST /webhooks/inbound-email` for Mailgun/Postmark inbound
   parse. Signed with `INBOUND_EMAIL_SECRET` (HMAC-SHA256 over the raw body in the
   `X-Webhook-Signature` header). The submitter's email is stored only as a hash.
3. **Offline ingest** — `php artisan rants:ingest path/to/file.eml` (or a directory
   of `.eml` files) for local testing without a mail provider.

Then: `ScrubRant` job runs the configured scrubber → moderator reviews the
original vs. scrubbed text at `/moderation` → **Approve & publish**.

## The scrubber

Pluggable via `config/scrubber.php` (`SCRUBBER_DRIVER`):

- **`rule_based`** (default, offline) — redacts emails/phones/@handles/URLs and
  swaps known names + companies for consistent aliases (`app/Scrubbing/RuleBasedScrubber.php`).
- **`claude`** — Anthropic Messages API with structured JSON output
  (`app/Scrubbing/ClaudeScrubber.php`). Set `ANTHROPIC_API_KEY`. **Always fails
  safe** to the rule-based scrubber on error/refusal/empty output.

Both implement `App\Scrubbing\Contracts\RantScrubber` and are resolved through a
Laravel `Manager` (`app/Scrubbing/ScrubManager.php`).

## Features

- Public feed (latest / most read / most discussed), rant pages, user profiles (`/@username`)
- Threaded comments, follow users, bookmark rants, share (copy / X / Facebook / email)
- Crawler-safe Open Graph / Twitter meta rendered server-side (`resources/views/app.blade.php`)
- Minimal moderation: pending queue, approve/reject, report → remove, audit log
- Responsible-content guardrails: moderation gate, `noindex` + 404 on non-approved
  rants, hashed submitter emails, rate limits, terms acceptance, honeypot

## Tests

```bash
php artisan test     # 71 passing (Pest)
npm run lint         # ESLint
npm run types:check  # tsc --noEmit
```
