<script setup lang="ts">
type SitePreviewProduct = {
  id: number
  brand: string | null
  title: string
  slug: string
  image_url: string | null
  affiliate_url: string
  price: string | number | null
  old_price: string | number | null
  currency: string
  availability: string | null
  partner: { id: number; name: string } | null
  category: { id: number; name: string } | null
}

type SitePreviewResponse = {
  site: {
    id: number
    name: string
    slug: string
    primary_domain: string
    domain_aliases: string[]
    locale: string
    currency: string
    timezone: string
    theme: Record<string, string>
    layout: Record<string, string>
    settings: Record<string, string>
    is_active: boolean
    counts: {
      categories: number
      feeds: number
      products: number
    }
  }
  products: SitePreviewProduct[]
}

const route = useRoute()
const config = useRuntimeConfig()

const slug = computed(() => String(route.params.slug))
const { data, error, pending } = await useFetch<SitePreviewResponse>(
  () => `${config.public.apiBase}/sites/preview/${slug.value}`,
  { server: false },
)

const site = computed(() => data.value?.site)
const products = computed(() => data.value?.products ?? [])

const themeStyle = computed(() => {
  const theme = site.value?.theme ?? {}

  return {
    '--site-primary': theme.primary_color || theme.primary || '#0f766e',
    '--site-accent': theme.accent_color || theme.accent || '#d97706',
    '--site-surface': theme.surface_color || theme.surface || '#ffffff',
    '--site-font': theme.font_family || theme.font || 'Inter, ui-sans-serif, system-ui, sans-serif',
  }
})

const templateLabel = computed(() => {
  const layout = site.value?.layout ?? {}

  return layout.home_template || layout.template || 'home_default'
})

function formatPrice(amount: string | number | null, currency: string): string {
  if (amount === null || amount === '') {
    return '-'
  }

  return new Intl.NumberFormat((site.value?.locale || 'nl_NL').replace('_', '-'), {
    style: 'currency',
    currency,
  }).format(Number(amount))
}

useHead(() => ({
  title: site.value ? `Preview ${site.value.name}` : 'Site preview',
}))
</script>

<template>
  <main class="preview" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Loading preview...</p>
    </section>

    <section v-else-if="error || !site" class="state">
      <p class="eyebrow">Preview unavailable</p>
      <h1>Site not found</h1>
    </section>

    <template v-else>
      <header class="preview-bar">
        <div>
          <strong>Preview mode</strong>
          <span>{{ site.primary_domain }}</span>
        </div>
        <span :class="['status', site.is_active ? 'status-active' : 'status-inactive']">
          {{ site.is_active ? 'Active' : 'Inactive' }}
        </span>
      </header>

      <section class="hero">
        <div class="hero-copy">
          <p class="eyebrow">{{ templateLabel }}</p>
          <h1>{{ site.name }}</h1>
          <p>
            Storefront preview for {{ site.primary_domain }}. This route does not
            need DNS and uses the same Site configuration the real domain will use.
          </p>
          <dl class="metrics">
            <div>
              <dt>Products</dt>
              <dd>{{ site.counts.products }}</dd>
            </div>
            <div>
              <dt>Feeds</dt>
              <dd>{{ site.counts.feeds }}</dd>
            </div>
            <div>
              <dt>Locale</dt>
              <dd>{{ site.locale }}</dd>
            </div>
          </dl>
        </div>
      </section>

      <section class="products">
        <div class="section-heading">
          <p class="eyebrow">Catalog</p>
          <h2>Latest imported products</h2>
        </div>

        <div v-if="products.length" class="product-grid">
          <NuxtLink
            v-for="product in products"
            :key="product.id"
            class="product-card"
            :to="`/preview/${site.slug}/products/${product.slug}`"
          >
            <div class="product-image">
              <img v-if="product.image_url" :src="product.image_url" :alt="product.title">
              <span v-else>No image</span>
            </div>
            <div class="product-body">
              <p v-if="product.brand" class="brand">{{ product.brand }}</p>
              <h3>{{ product.title }}</h3>
              <p class="price">{{ formatPrice(product.price, product.currency) }}</p>
              <p v-if="product.availability" class="availability">
                {{ product.availability.replaceAll('_', ' ') }}
              </p>
            </div>
          </NuxtLink>
        </div>

        <div v-else class="empty">
          <h3>No products imported yet</h3>
          <p>
            Once a feed is imported for this Site, products will appear here using
            their feed-provided product images and catalog fields.
          </p>
        </div>
      </section>
    </template>
  </main>
</template>

<style scoped>
.preview {
  min-height: 100vh;
  color: #1b2528;
  background: #f5f7f2;
  font-family: var(--site-font);
}

.preview-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px clamp(20px, 4vw, 56px);
  color: #ffffff;
  background: #1f2933;
}

.preview-bar div {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
  align-items: baseline;
}

.preview-bar span {
  color: #d8e0e6;
}

.status {
  padding: 4px 9px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 700;
}

.status-active {
  background: #0f766e;
}

.status-inactive {
  background: #9a3412;
}

.hero {
  min-height: 54vh;
  display: grid;
  align-items: end;
  padding: clamp(56px, 8vw, 108px) clamp(20px, 4vw, 56px);
  color: #ffffff;
  background:
    linear-gradient(125deg, rgba(15, 118, 110, 0.92), rgba(31, 41, 51, 0.8)),
    var(--site-primary);
}

.hero-copy {
  width: min(920px, 100%);
}

.eyebrow {
  margin: 0 0 12px;
  color: var(--site-accent);
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

.hero .eyebrow {
  color: #ffe8b6;
}

h1,
h2,
h3,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 18px;
  font-size: 5rem;
  line-height: 0.95;
}

.hero p {
  max-width: 680px;
  margin-bottom: 28px;
  font-size: 1.18rem;
  line-height: 1.65;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1px;
  width: min(620px, 100%);
  margin: 0;
  overflow: hidden;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.24);
}

.metrics div {
  padding: 16px;
  background: rgba(255, 255, 255, 0.12);
}

.metrics dt {
  color: rgba(255, 255, 255, 0.76);
  font-size: 0.76rem;
  font-weight: 800;
  text-transform: uppercase;
}

.metrics dd {
  margin: 6px 0 0;
  font-size: 1.35rem;
  font-weight: 800;
}

.products {
  padding: clamp(36px, 6vw, 72px) clamp(20px, 4vw, 56px);
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.section-heading h2 {
  margin: 0;
  font-size: 2.5rem;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 18px;
}

.product-card {
  display: block;
  overflow: hidden;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: var(--site-surface);
  color: inherit;
  text-decoration: none;
  transition: border-color 160ms ease, transform 160ms ease;
}

.product-card:hover {
  border-color: var(--site-primary);
  transform: translateY(-2px);
}

.product-image {
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  background: #e9eee9;
  color: #65726f;
  font-weight: 700;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.product-body {
  padding: 16px;
}

.brand {
  margin-bottom: 8px;
  color: var(--site-primary);
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.product-body h3 {
  min-height: 3.2em;
  margin-bottom: 14px;
  font-size: 1rem;
  line-height: 1.35;
}

.price {
  margin-bottom: 6px;
  font-size: 1.2rem;
  font-weight: 850;
}

.availability {
  margin-bottom: 0;
  color: #52615d;
  text-transform: capitalize;
}

.empty,
.state {
  width: min(760px, 100%);
  padding: 32px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
}

.state {
  margin: 64px auto;
}

.empty p {
  max-width: 580px;
  margin-bottom: 0;
  color: #52615d;
  line-height: 1.6;
}

@media (max-width: 720px) {
  .preview-bar,
  .section-heading {
    align-items: start;
    flex-direction: column;
  }

  .metrics {
    grid-template-columns: 1fr;
  }

  h1 {
    font-size: 2.5rem;
  }

  .section-heading h2 {
    font-size: 1.8rem;
  }
}
</style>
