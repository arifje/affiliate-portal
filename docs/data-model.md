# Data Model

The first catalog schema is designed around explicit site ownership and feed
normalization.

## Sites

Stores domain identity, branding, locale, currency, theme tokens, and layout
settings. Domains are unique at the primary-domain level; aliases live in JSON
so parked domains can be added without schema churn.

## Partners and Feeds

Partners represent affiliate networks or direct merchants. Feeds represent a
single import source for a specific site and partner. Feed metadata tracks
provider type, URL, credentials/config, scheduling, and last import state.

## Categories

Categories are site-scoped and support nested trees. Slugs are unique per site
and parentage is optional.

## Products

Products are normalized records used by every public frontend. They retain
provider identifiers for deduplication, include affiliate URLs for outbound
redirects, and expose indexed fields for catalog browsing and search.

## Clicks

Clicks record outbound redirect attempts. The table stores site/product/feed
references plus request metadata, enabling site analytics and partner reporting
without relying on third-party dashboards alone.
