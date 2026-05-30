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
Feeds can point to a reusable mapping profile, which describes how source fields
from Daisycon, Awin, TradeTracker, or custom feeds are normalized.

## Feed Mapping

`canonical_fields` stores the universal product vocabulary. `feed_mapping_profiles`
stores provider or feed-specific parsing defaults. `feed_field_mappings` connects
source fields or paths to canonical fields and records transforms such as money,
availability, integer, boolean, or array normalization.

Import runs are tracked in `feed_import_batches`; failed rows are captured in
`feed_import_row_errors` for debugging and feed onboarding.

See [Feed Mapping](feed-mapping.md) for the full mapping workflow.

## Categories

Categories are site-scoped and support nested trees. Slugs are unique per site
and parentage is optional.

## Products

Products are normalized records used by every public frontend. They retain
provider identifiers for deduplication, include affiliate URLs for outbound
redirects, and expose indexed fields for catalog browsing and search. Common
affiliate fields such as MPN, merchant category, product type, shipping cost,
stock quantity, delivery time, and variant attributes are columns. Less common
attributes stay in `metadata`, while `raw_payload` can retain the original source
row for traceability.

## Clicks

Clicks record outbound redirect attempts. The table stores site/product/feed
references plus request metadata, enabling site analytics and partner reporting
without relying on third-party dashboards alone.
