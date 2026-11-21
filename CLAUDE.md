# CLAUDE.md — Future Code Admin Template

Guidance for Claude Code when working in this repository.

## Project

A production-ready Laravel + Filament admin template for Future Code (كود المستقبل).
It is a **foundation reused across projects** — every decision here is multiplied by
every project built on it. Prefer configurability over hardcoding, always.

**The template is implemented.** Before changing anything, read in this order:

1. `docs/21-implementation-notes.md` — where the code deviates from the docs, and why.
   Several doc snippets are wrong against the installed versions; this file lists them.
2. `PRODUCTION-READINESS.md` — every acceptance criterion and its verified state.
3. `docs/01-architecture-ddd.md`, `docs/18-conventions.md`, `docs/19-policies.md`,
   `docs/20-security.md` — the mandatory four.

## Stack (verified working — do not change without discussion)

- PHP 8.5 (constraint `^8.4`) · Laravel 13.29 · Filament 5.7.6 · Livewire 4.4.2 · Tailwind 4
- PostgreSQL 18 · Redis 8 · Node 22+
- Multi-tenant on `stancl/tenancy` 3.10, mode chosen on first deploy and locked:
  `single` (one database, `tenant_id` rows) or `multi` (database per tenant + central)
- Two Filament panels: `/admin/t/{slug}` (tenant) and `/central` (operations team, `docs/24`)
- MCP server for AI apps (Claude, ChatGPT) at `/mcp`: `laravel/mcp` **1.0.1 pinned exactly** +
  `laravel/passport` 13 for OAuth (`docs/23`)

## Non-negotiable rules

1. **No hardcoding.** Colors, permissions, disks, settings, and copy all come from
   config, database, or translation files.
2. **All authorization goes through Laravel Policies. No exceptions.**
   `hasPermissionTo()` / `hasRole()` may appear in exactly three places:
   `Src\Support\Domain\Authorization\Decision`, the `Policies` themselves, and
   `AuthorizationServiceProvider`. Everywhere else calls `can($ability, $model)`.
   Central roles (`super_admin`, `support`) are asked through `CentralRoles` — it
   queries team `0` explicitly, because `hasRole()` only sees the *current* team.
   - `$user->can('publish.announcements')` ❌ — that is a permission *name*
   - `$user->can('publish', $announcement)` ✅ — that is an *ability*
3. **Every UI element is authorization-gated**, and the endpoint behind it is
   independently authorized. Hiding is UX; the policy is security.
4. **Every tenant-owned model uses `BelongsToTenant`.** A query without tenant
   context throws rather than returning all rows.
5. **Zero literal strings in code.** Everything goes through `__()`.
6. **Zero directional CSS.** `start`/`end`, never `left`/`right`.
7. **Business logic lives in Application Actions**, never in Filament Resources.
8. **`env()` only inside `config/`.**
9. **Every screen ships its onboarding:** a Tour (≤ 5 steps, `ar` + `en`, `panels()` set)
   and its own educational empty state. Features that need setup also ship an
   `OnboardingStep`. Exemptions go in `fc-onboarding.exempt` with a translated reason.
10. **Every screen ships three test files:** Authorization matrix, Acceptance
    (`it()` per acceptance criterion), and a browser Journey (sidebar → tour → CRUD →
    denied role, with `assertUiQuality` in `ar`/`en`, mobile, dark). `docs/17` §١٣.
11. **Both tenancy modes stay green.** `composer check` runs `single` and `multi`;
    CI runs both for the suite and for the browser journeys.
12. **Every resource is fully usable by AI, and every screen is known to it.** Each Filament
    resource ships an MCP declaration (`Presentation/Mcp/<Model>Mcp.php`, or `Mcp/Central/`).
    Every public Policy ability is either exposed (operation, `McpRecordAction`, or field
    `->ability()`) or listed in `omitted()` with a translated reason. Screens need nothing extra:
    the system map discovers them and describes them from their Tour.

Arch tests enforce 1–10 and 12 (`OnboardingCoverageTest`, `FeatureTestCoverageTest`,
`McpCoverageTest`); CI enforces 11.
Run them and they will tell you which rule you broke.

## Layout

```
src/
├── Support/                  # shared: Policy, Decision, TenantContext, DiskResolver, theming
│   ├── Application/Actions/Records/   # generic List/Show/Create/Update/Delete actions (Gate inside)
│   └── Presentation/Mcp/              # MCP engine: server, registry, session, tools, SystemMap
└── Contexts/<Name>/          # Identity · Tenancy · Content · Media · Notifications · Settings · Audit · Ai
    ├── Domain/               # models, enums, value objects, events, repository interfaces
    ├── Application/          # actions (use cases), DTOs, onboarding steps
    ├── Infrastructure/       # repositories, policies, notifications, listeners, exporters
    ├── Presentation/         # Filament resources, pages, widgets, tours, views
    │   ├── Filament/Central/ # screens for the /central panel (discovered separately)
    │   └── Mcp/              # MCP declarations (`<Model>Mcp.php`); `Mcp/Central/` for /central
    ├── Database/Migrations/  # central tables
    │   └── Tenant/           # tenant tables — `migrate` in single, `tenants:migrate` in multi
    ├── Lang/ Routes/ Tests/
    └── <Name>ServiceProvider.php     # extends ContextServiceProvider

packages/future-code/filament-onboarding/   # internal package (path repo)
```

Planned, not implemented yet: an `Integrations` context for webhooks (`docs/22`).

Dependency direction: `Presentation → Application → Domain`.
Contexts communicate via **domain events** only — never by importing each other's models.

## Commands

```bash
composer test         # pest (unit, feature, arch, acceptance), TENANCY_MODE=single
composer test:multi   # the same suite in database-per-tenant mode — both must pass
composer test:browser # journeys in real Chromium — needs `npm run build` first
composer lint         # pint --test + phpstan level 6
composer fix          # pint
composer check        # lint + test + test:multi + composer audit

npx playwright install chromium   # once, for test:browser

php artisan tenancy:init --mode=single|multi   # once, on first deploy (deploy.sh does it)

php artisan authorization:sync        # permissions from config/authorization.php
php artisan make:fc-feature <Context> <Model> [--translatable --soft-deletes]
php artisan media:move --from=local --to=s3 --dry
php artisan passport:keys            # once per environment — OAuth signing keys (deploy.sh does it)
php artisan mcp:inspector mcp        # try the MCP tools by hand (OAuth, like any client)
php artisan db:seed --class=DemoSeeder   # 50k rows for performance work
```

Tests run on **PostgreSQL**, not SQLite. Create `fc_admin_testing` first.
Pest and PHPStan need a raised memory limit — the composer scripts already set it.

## Adding a feature

`php artisan make:fc-feature` scaffolds the structure; `docs/16-adding-a-feature.md`
is the full recipe. The order matters:

1. Permissions in `config/authorization.php` + translations + `authorization:sync`
2. Migration (`tenant_id`, composite index starting with it, `softDeletes`, `json` for translatable)
3. Domain: Enum → Model (with `BelongsToTenant`) → Event
4. Application: DTO → Action (with `Gate::authorize()` as the last barrier)
5. Infrastructure: **Policy (permission + business rules together)** → Listener → Notification
6. Presentation: Filament Resource — `->authorize()` only, never a permission string.
   Plus its Tour and its own empty state (`Presentation/Filament/Central/` + `Tours/Central/` for `/central`).
   Plus its MCP declaration (`Presentation/Mcp/<Model>Mcp.php` — copy `AnnouncementMcp`, `docs/23` §3)
7. Translations `ar` + `en`, including the policy's denial messages and tour copy
8. Tests: role matrix + Acceptance (one test per business rule, tenant isolation,
   super-admin invariants) + browser Journey — copy `AnnouncementJourneyTest`, or add a row
   to `ScreensJourneyTest` for a screen without CRUD
9. `composer check`, then `npm run build && composer test:browser`

A new context also needs: registration in `bootstrap/providers.php`, and its
migrations path in `phpstan.neon` under `databaseMigrationsPath`.

## Settings pages are tabs, not sidebar items

Global settings live under **one** `/central` sidebar entry, `SettingsCluster`
(`src/Contexts/Settings/Presentation/Filament/Central/Clusters/`); tenant groups live in
`TenantSettingsCluster` (`Presentation/Filament/Clusters/`). Each settings group is a
tab at the top of the page (`SubNavigationPosition::Top`). Full pattern: `docs/05`.

To add a settings group, add a `SettingsPage` — **never a new sidebar item**:

- `protected static ?string $cluster = SettingsCluster::class;`
- `protected static ?string $slug = 'payments';` — a property, not a `getSlug()`
  override. The cluster adds the `settings/` prefix itself.
- `$navigationSort` sets the tab order.
- **No `getNavigationGroup()`** on the page. The cluster owns the group.
- `canAccess()` = `Gate::allows('access.system') && Gate::allows('manage_<group>.settings')`,
  with the permission added under `settings.extra` in `config/authorization.php` and labelled in `ar` + `en`.
- Add a case to `SettingsAcceptanceTest`.

The same applies to any other set of related pages: group them in a `Cluster`
under `Presentation/Filament/Clusters/` (the registry discovers it automatically).
Don't add them as separate sidebar items.

## Webhooks — `docs/22-webhooks.md`

Not implemented yet. The doc is the recipe and these rules are non-negotiable:

- **Inbound:** route outside the panel/`web` stack; CSRF exemption for that path only.
  Verify the HMAC signature on the **raw body** with `hash_equals`, plus a timestamp
  tolerance, **before** storing or logging anything. Idempotency comes from a **unique
  constraint** on the provider event id, not a check before insert. Store the call, return
  `200` right away, and process it in a queued job.
- **Tenant comes from the endpoint** (the route binding or its secret), **never from the payload**.
- The job fires a domain event, and an Application Action does the work. Never trust
  amounts or statuses from the payload. Re-fetch them from the provider.
- **Outbound:** dispatch from a listener on a domain event, never from a Resource or Action.
  The payload is a versioned DTO envelope, never `$model->toArray()`. Sign it with HMAC-SHA256
  and a timestamp. Retry with exponential backoff on a dedicated queue, and auto-disable the endpoint after repeated failures.
- **SSRF guard** on save *and* on send: https only, reject private/loopback/link-local/metadata
  addresses (IPv4 + IPv6), don't follow redirects.
- Secrets are generated inside an Action, shown once, stored encrypted, and rotated with an
  overlap window. Signature headers go in `logging.redact`.

## MCP server (AI access) — `docs/23-mcp-server.md`

**Implemented.** Users connect Claude / ChatGPT / Claude Code to `/mcp` via OAuth (Passport,
dynamic client registration, PKCE). On the consent screen they pick **one organization** and
**read** or **read & write**. **The AI is an ordinary user with fewer permissions, not a back door.**

**Adding a resource = one declaration file.** `Contexts/<X>/Presentation/Mcp/<Model>Mcp.php`
extends `McpResource` (copy `AnnouncementMcp`). It generates `{resource}_list/_get/_create/_update/
_delete/_restore/_force_delete` plus one tool per `McpRecordAction`:

- `filamentResource()` — model, labels, URLs and the base query (`getEloquentQuery()`) come from it.
- `fields()` — `McpField::string/text/html/enum/boolean/dateTime/...`, with `->label()` set to the
  form's translation key. Use `->translatable()`, `->required()`, `->readOnly()` (changed only by an
  action, like `status`), `->ability('pin')` (field-level policy), `->filterable()`, `->sortable()`.
- `operations()` — read-only by default; add the writes the panel allows.
- `actions()` — each custom table button becomes a `McpRecordAction` that calls **the same
  Application Action**. If the button did `$record->update()` inline, move that into an Action first.
- `omitted()` — every Policy ability you do not expose, mapped to a translated reason (`mcp.omitted.*`).
- `creating()` — server-set values on create (like `mutateFormDataBeforeCreate`).

Pages need nothing: `SystemMap` reads the panels on every request, and describes each screen from its
Tour (or `DescribesForAi`, or the onboarding exemption reason). The AI sees them through
`describe_system`, `get_screen`, and the `fc://system/map` resource — only the screens the user can open.

Non-negotiable:

- One tool = one Application Action with `Gate::authorize()`. No queries, `DB::`, or `->save()` inside
  `Presentation/Mcp` (arch test). Hand-written tools extend `FcTool` (logging, denial messages,
  write gate, rate limit, output limit) and go in `Presentation/Mcp/`.
- `shouldRegister()` hides a tool (`Decision::holds()`, same as hiding a Filament button). It is UX,
  not security — the Policy still runs on the record. A hidden tool called by name is "not found".
- **The tenant comes from the connection** (`ai_connections`: user + OAuth client → one tenant), set by
  `AuthenticateMcpConnection`. **No `tenant_id` in any tool schema.** Consenting the same client to
  another tenant revokes its old tokens.
- Explicit annotations on every tool; writes that change/delete existing data are `destructiveHint`.
  No bulk tools — one record per call.
- Never expose: `ai.forbidden_abilities` (impersonation, roles, password reset, force logout,
  export, prune, change disk) and no writes on `ai.read_only_resources` (users, roles, settings,
  activity log). Global settings and anything in `encrypted()` have no declaration.
- Every call is logged to the activity log (`mcp`, redacted input, outcome + reason).
- Three kill switches, all must be open: `MCP_ENABLED` → `SecuritySettings::mcp_enabled` →
  tenant setting `ai.mcp_enabled` (+ `ai.allow_writes`). `/central` over MCP is off by default
  (`MCP_CENTRAL_ENABLED`). No `Mcp::local` (arch test).
- Allowed OAuth redirect hosts live in `MCP_REDIRECT_DOMAINS` (`config/mcp.php`) — never `*`.
- `laravel/mcp` stays pinned to an exact version; upgrades need a changelog review.

## Before finishing any task

- [ ] `composer check` passes (lint + tests in both tenancy modes + audit)
- [ ] `composer test:browser` passes after `npm run build`
- [ ] New screen has a Tour, its own empty state, and Authorization + Acceptance + Journey tests
- [ ] Browser selectors use `buttonNamed()` / `navLinkSelector()`, not guessed Filament classes
- [ ] No hex colors outside the theme file / `Infrastructure/Theming`
- [ ] No literal user-facing strings
- [ ] No `pl-`/`pr-`/`ml-`/`mr-`/`text-left`/`text-right`
- [ ] No `hasPermissionTo()` / `hasRole()` outside the authorization layer
- [ ] No `can('action.resource')` permission strings
- [ ] No `->skipAuthorization()`
- [ ] Bulk actions use `->authorizeIndividualRecords()`
- [ ] Every policy method returns `Response`, not `bool`
- [ ] Sensitive form fields block persistence (`->saved(false)`), with a test proving it
- [ ] New model with `tenant_id` uses `BelongsToTenant`
- [ ] New permission has `ar` and `en` labels
- [ ] Every catalog entry in `config/notifications.php` has a notification class behind it
- [ ] Every migration has a `down()` that actually drops what `up()` created
- [ ] Query count for any table page ≤ 10 — and **no query inside a policy business
      rule that varies per row** (use `PolicyMemo`)
- [ ] A new settings group is a tab in `SettingsCluster`, not a sidebar item
- [ ] New Filament resource has an MCP declaration; every Policy ability is exposed or `omitted()`
      with a translated reason (`McpCoverageTest`)
- [ ] A table button that changes data calls an Application Action — the MCP tool calls the same one
- [ ] Any webhook or MCP work follows the `docs/22` / `docs/23` acceptance lists

## Things that are easy to get wrong here

These are all real, all hit during implementation — details in `docs/21`:

- **Tenancy (`docs/21` §30).** A raw `DB::table()` on a central table must name the
  central connection — in `multi` the default connection is the tenant database.
  Tenant migrations live in `Database/Migrations/Tenant` and use `TenantBlueprint::constrain()`,
  never `->constrained()` (no cross-database foreign keys). `withoutScope()` throws in `multi`;
  use `forEachTenant()`. `RefreshDatabase` cannot wrap `CREATE DATABASE` — `multi` tests truncate.
- **Context middleware must run before `Authenticate`.** Laravel prioritises
  `Authenticate`, and `canAccessPanel()` checks permissions on the *current* spatie team.
  `bootstrap/app.php` prepends `InitializeTenantContext`/`EndTenancy` to the priority list;
  without it the first request on every worker is a 403 while tests stay green.
- **Central staff have no tenant roles.** `super_admin` lives on team `0`. `Gate::before`
  and `Decision::permission()` ask `CentralRoles`/`CentralAuthority`; never `hasRole('super_admin')`.
- **Screens for `/central` go in `Presentation/Filament/Central/`**, tenant screens stay in
  `Presentation/Filament/`. A settings field on a tenant page needs a reader that goes through
  `TenantSettings::get()`, or the value saves and nothing uses it.

- **`Panel::configureUsing()` runs before `->id()`**, so `$panel->getId()` is empty.
  Resource discovery goes through `ContextPanelRegistry`, not that callback.
- **A policy business rule that calls `can('otherAbility', $record)` can deadlock
  logically** — `AnnouncementPolicy::update` asking `can('publish', $a)` made
  published announcements uneditable forever. Ask about the *permission* instead.
- **The policy runs once per row per action.** Any query inside it multiplies by the
  page size. Eager-load what the policy reads; memoize page-wide answers in `PolicyMemo`.
- `Decision::permission()` denies **without a message** on purpose → the action
  disappears. `rule()` always carries a message → the action shows disabled with the reason.
- `shouldRegisterNavigation()` hides the link but does **not** block the URL. For a
  whole area, use `Policy::area()`.
- **A Filament `Cluster` has no `canAccess()` by default.** It hides its sidebar entry when
  no page inside is accessible, but its URL still renders an empty page with `200`.
  `SettingsCluster::canAccess()` is explicit for this reason. A cluster URL answers **302**
  (redirect to its first tab), so `NavigationReachabilityTest` accepts a redirect for clusters
  only. Its privileged-path filter must match the cluster URL, which has no trailing slash.
- A page inside a cluster that keeps a `getNavigationGroup()` renders its tab under a
  sub-heading, and a `getSlug()` that still includes the cluster prefix doubles it
  (`settings/settings/mail`).
- `Gate::before` returning `true` bypasses the policy entirely, business rules included.
  It must consult `InvariantRegistry` and return `null` for guarded abilities.
- `Gate::before` must return `null` (not `false`) for non-super-admins.
- Models live outside `App\Models`, so policy discovery needs `Gate::guessPolicyNamesUsing()`.
- `->authorize('x')` on a Filament action auto-prepends the record — do not pass it yourself.
- **spatie/permission teams:** set the tenant context *before* `assignRole()`, always.
  The seeder and the Pest helpers both do this; copy the pattern.
- **activitylog v5** moved `LogsActivity` and `LogOptions`, renamed
  `dontSubmitEmptyLogs()` → `dontLogEmptyChanges()`, and **removed `tapActivity()`**.
- **`notifications.data` must be `json`** on Postgres, not `text` — Filament queries `->>`.
- **Every table column needs a real `->label()`**, icon columns included — the tables
  are reorderable and a blank label throws at render time.
- **Context translations register in `register()`, never `boot()`.** Package providers boot
  first; Filament builds the panel in its `boot()` and evaluates labels then. If the
  namespace is not registered yet, the Translator caches the empty result for the whole
  request and the entire panel renders raw keys — while still returning 200.
- **`env()` in `bootstrap/app.php` returns `null` once config is cached** — i.e. in
  production. Security-relevant values (trusted proxies) must live in `config/` and be
  applied from a provider's `boot()`.
- **`bootstrap/cache/config.php` overrides `phpunit.xml` env vars.** Never run tests with
  config cached — they will hit the dev database. Always `optimize:clear` first.
- **Media `tenant_id` is injected in `MediaItem::booted()`, not in the upload trait** —
  Filament's `SpatieMediaLibraryFileUpload` bypasses the trait entirely.
- Queued jobs carry no tenant context — extend `ContextAwareJob`.
- Filament v5 requires explicit `->columnSpanFull()` on `Section`/`Grid`/`Fieldset`.
- Row actions are `->recordActions()`, toolbar/bulk are `->toolbarActions()`.
- Dark-mode status colors (`#4ADE80`, `#FBBF24`, `#F87171`) fail contrast on white —
  never use them in light mode.
- **MCP (`docs/21` §31).** `auth:api` switches the default guard to `api`, while Filament asks the
  `web` guard — `AuthenticateMcpConnection` sets the user on `web` too, or every `canAccess()` sees a
  guest. Passport switches (`$deviceCodeGrantEnabled`) must be set in `register()`: Passport registers
  its routes in its own `boot()`, before ours. Translatable columns are `json` — never `orderBy()` them.
  A test that closes `SecuritySettings::mcp_enabled` must reopen it: `settings` is not truncated in `multi`.
  Ask `access.ai` on the **target tenant's team** (`AiAvailability::userMayConnect()`), never the
  current one — the consent screen runs with the central team. Passport's own routes are off
  (`Passport::ignoreRoutes()`): its approve route and auto-approval bypassed `connect`; consent is
  forced (`RequireConsentPrompt`). `McpField::sortable()` only on `NOT NULL` columns (cursor pagination).

## Security

`docs/20-security.md` is a set of acceptance conditions, not advice. The three
highest-risk areas, in order:

1. **Cross-tenant leakage** — a query that forgot its scope. Silent, so the arch test
   walks every model automatically.
2. **Privilege escalation** — a hidden button whose endpoint is open, or a `disabled()`
   field whose value still saves.
3. **Stored XSS from the rich editor** — sanitize on save *and* on output.

Never `$guarded = []`. Never `{!! !!}` on unsanitized content. Never `whereRaw`
with user input. Never an SVG upload into a public collection.
