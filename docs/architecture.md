# Architecture

Affiliate Portal is a multi-domain affiliate platform with one Laravel backend,
one Nuxt frontend, and one shared database.

## Domain Resolution

Incoming domains are resolved through the `sites` table. Each site stores its
primary domain, optional aliases, locale, currency, theme tokens, and layout
settings. The Nuxt SSR app can read the request host and request the matching
site configuration from the backend.

For local review and pre-DNS work, the frontend also supports preview URLs in
the form `/preview/{site-slug}`. This resolves a site by slug through the backend
API and renders the same Site configuration without requiring the real domain to
point at the application yet.

Example sites:

- `maskers.nl`
- `hartslagmeters.nl`
- `computeronderdelen.nl`
- `sweaters.nl`
- `ponchos.nl`

## Data Ownership

The backend owns catalog, feed, and tracking data. The frontend renders public
site experiences and should not contain hard-coded domain logic beyond host
detection and fallback behavior.

Core relationships:

- A `site` has many `categories`, `feeds`, `products`, and `clicks`.
- A `partner` has many `feeds` and `products`.
- A `feed` belongs to one `site` and one `partner`.
- A `product` belongs to one `site`, one `partner`, and optionally one `feed`
  and category.
- A `click` records redirect intent and preserves product/feed/site context.

## Feed Normalization

Daisycon, Awin, TradeTracker, and custom sources should be imported through a
data-driven mapping layer. Provider templates map raw feed rows into a canonical
field registry first, and that canonical row is then written into the shared
`products` schema. Provider identifiers remain available for deduplication and
debugging, while public rendering uses normalized fields.

## Search

The first version uses MariaDB indexes and full-text search on normalized
product fields. This is appropriate for the initial target below 100k products.
A future external search service can be introduced behind an indexing/search
service without changing the public frontend API.

## Admin

Filament should run inside the Laravel app and manage all sites from one admin
panel. Domain-specific permissions can be added later if editorial teams need
site-scoped access.
