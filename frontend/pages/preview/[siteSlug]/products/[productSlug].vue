<script setup lang="ts">
type ProductCard = {
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
  category: { id: number; name: string; slug?: string } | null
}

type ProductDetail = ProductCard & {
  description: string | null
  product_url: string | null
  tracking_url: string | null
  additional_image_urls: string[]
  condition: string | null
  shipping_cost: string | number | null
  stock_quantity: number | null
  delivery_time: string | null
  color: string | null
  size: string | null
  gender: string | null
  material: string | null
  pattern: string | null
  age_group: string | null
  merchant_category: string | null
  product_type: string | null
  metadata: {
    highlights?: string[]
    battery_life?: string
    connectivity?: string[]
    [key: string]: unknown
  }
  partner: { id: number; name: string; website_url: string | null } | null
}

type SitePreviewProductResponse = {
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
  product: ProductDetail
  related_products: ProductCard[]
}

const route = useRoute()
const config = useRuntimeConfig()

const siteSlug = computed(() => String(route.params.siteSlug))
const productSlug = computed(() => String(route.params.productSlug))
const apiBase = computed(() => import.meta.server ? config.apiBase : config.public.apiBase)

const { data, error, pending } = await useFetch<SitePreviewProductResponse>(
  () => `${apiBase.value}/sites/preview/${siteSlug.value}/products/${productSlug.value}`,
)

const isWebsiteOffline = computed(() => {
  const fetchError = error.value as { status?: number; statusCode?: number } | null

  return (fetchError?.statusCode ?? fetchError?.status) === 503
})
const site = computed(() => data.value?.site)
const product = computed(() => data.value?.product)
const relatedProducts = computed(() => data.value?.related_products ?? [])
const trackedViewKey = ref<string | null>(null)
const recentlyViewedProducts = ref<ProductCard[]>([])
let stopSiteVisitHeartbeat: (() => void) | null = null

const themeStyle = computed(() => {
  const theme = site.value?.theme ?? {}

  return {
    '--site-primary': theme.primary_color || theme.primary || '#0f766e',
    '--site-accent': theme.accent_color || theme.accent || '#d97706',
    '--site-eyebrow': theme.eyebrow_color || theme.accent_color || theme.accent || '#d97706',
    '--site-surface': theme.surface_color || theme.surface || '#ffffff',
    '--site-font': theme.font_family || theme.font || 'Inter, ui-sans-serif, system-ui, sans-serif',
  }
})

const highlights = computed(() => product.value?.metadata?.highlights ?? [])

const galleryImages = computed(() => {
  if (!product.value) {
    return []
  }

  return [
    product.value.image_url,
    ...(product.value.additional_image_urls ?? []),
  ].filter(Boolean) as string[]
})

const specs = computed(() => {
  if (!product.value) {
    return []
  }

  return [
    ['Merk', product.value.brand],
    ['Categorie', product.value.category?.name],
    ['Partner', product.value.partner?.name],
    ['Beschikbaarheid', formatAvailability(product.value.availability)],
    ['Levertijd', product.value.delivery_time],
    ['Verzendkosten', formatPrice(product.value.shipping_cost, product.value.currency)],
    ['Voorraad', product.value.stock_quantity],
    ['Conditie', product.value.condition],
    ['Kleur', product.value.color],
    ['Maat', product.value.size],
    ['Materiaal', product.value.material],
    ['Batterijduur', product.value.metadata?.battery_life],
  ].filter(([, value]) => value !== null && value !== undefined && value !== '')
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

function formatAvailability(value: string | null): string | null {
  return value?.replaceAll('_', ' ') ?? null
}

function recentlyViewedStorageKey(): string | null {
  if (!site.value) {
    return null
  }

  return `affiliate_portal_recently_viewed:${site.value.slug}`
}

function readRecentlyViewedProducts(): ProductCard[] {
  const key = recentlyViewedStorageKey()

  if (!key) {
    return []
  }

  try {
    const storedProducts = JSON.parse(window.localStorage.getItem(key) || '[]')

    if (!Array.isArray(storedProducts)) {
      return []
    }

    return storedProducts.filter((storedProduct): storedProduct is ProductCard => (
      typeof storedProduct === 'object'
      && storedProduct !== null
      && typeof storedProduct.id === 'number'
      && typeof storedProduct.title === 'string'
      && typeof storedProduct.slug === 'string'
    ))
  } catch {
    return []
  }
}

function productCardPayload(productDetail: ProductDetail): ProductCard {
  return {
    id: productDetail.id,
    brand: productDetail.brand,
    title: productDetail.title,
    slug: productDetail.slug,
    image_url: productDetail.image_url,
    affiliate_url: productDetail.affiliate_url,
    price: productDetail.price,
    old_price: productDetail.old_price,
    currency: productDetail.currency,
    availability: productDetail.availability,
    partner: productDetail.partner,
    category: productDetail.category,
  }
}

function rememberProductView(): void {
  if (!site.value || !product.value) {
    return
  }

  const key = recentlyViewedStorageKey()
  const currentProduct = productCardPayload(product.value)
  const previousProducts = readRecentlyViewedProducts()
    .filter((storedProduct) => storedProduct.slug !== currentProduct.slug)

  recentlyViewedProducts.value = previousProducts.slice(0, 4)

  if (!key) {
    return
  }

  try {
    window.localStorage.setItem(
      key,
      JSON.stringify([currentProduct, ...previousProducts].slice(0, 8)),
    )
  } catch {
    // Browsers can block localStorage in strict privacy modes; the page should still work.
  }
}

function trackProductView(): void {
  if (!site.value || !product.value) {
    return
  }

  const viewKey = `${site.value.slug}:${product.value.slug}`

  if (trackedViewKey.value === viewKey) {
    return
  }

  trackedViewKey.value = viewKey

  const endpoint = `${config.public.apiBase}/sites/preview/${site.value.slug}/products/${product.value.slug}/views`
  const body = new URLSearchParams({
    visitor_id: getOrCreateVisitorId(),
    path: window.location.pathname,
  })

  if (navigator.sendBeacon?.(endpoint, body)) {
    return
  }

  void $fetch(endpoint, {
    method: 'POST',
    body,
  }).catch(() => {
    trackedViewKey.value = null
  })
}

function trackOutboundClick(): void {
  if (!site.value || !product.value) {
    return
  }

  const endpoint = `${config.public.apiBase}/sites/preview/${site.value.slug}/products/${product.value.slug}/clicks`
  const body = new URLSearchParams({
    visitor_id: getOrCreateVisitorId(),
    path: window.location.pathname,
    target_url: product.value.affiliate_url,
  })

  if (navigator.sendBeacon?.(endpoint, body)) {
    return
  }

  void $fetch(endpoint, {
    method: 'POST',
    body,
  }).catch(() => {})
}

onMounted(() => {
  watch(site, (currentSite) => {
    stopSiteVisitHeartbeat?.()
    stopSiteVisitHeartbeat = currentSite ? startSiteVisitHeartbeat(currentSite.slug) : null
  }, { immediate: true })

  watch(product, () => {
    rememberProductView()
    trackProductView()
  }, { immediate: true })
})

onBeforeUnmount(() => {
  stopSiteVisitHeartbeat?.()
})

useHead(() => ({
  title: product.value && site.value ? `${product.value.title} | ${site.value.name}` : 'Product preview',
}))
</script>

<template>
  <main class="product-page" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Loading product...</p>
    </section>

    <section v-else-if="isWebsiteOffline" class="state">
      <p class="eyebrow">Offline</p>
      <h1>Website tijdelijk offline</h1>
      <p>Deze website is momenteel niet publiek beschikbaar.</p>
    </section>

    <section v-else-if="error || !site || !product" class="state">
      <p class="eyebrow">Preview unavailable</p>
      <h1>Product not found</h1>
      <NuxtLink class="back-link" :to="`/preview/${siteSlug}`">
        Back to catalog
      </NuxtLink>
    </section>

    <template v-else>
      <header class="preview-bar">
        <div>
          <strong>Preview mode</strong>
          <span>{{ site.primary_domain }}</span>
        </div>
        <NuxtLink :to="`/preview/${site.slug}`">
          Back to catalog
        </NuxtLink>
      </header>

      <section class="product-hero">
        <div class="gallery">
          <div class="main-image">
            <img v-if="product.image_url" :src="product.image_url" :alt="product.title">
            <span v-else>No image</span>
          </div>
          <div v-if="galleryImages.length > 1" class="thumb-row">
            <img
              v-for="image in galleryImages"
              :key="image"
              :src="image"
              :alt="product.title"
            >
          </div>
        </div>

        <div class="product-copy">
          <p v-if="product.category" class="eyebrow">
            {{ product.category.name }}
          </p>
          <h1>{{ product.title }}</h1>
          <p v-if="product.brand" class="brand">
            {{ product.brand }}
          </p>

          <div class="price-row">
            <span class="price">{{ formatPrice(product.price, product.currency) }}</span>
            <span v-if="product.old_price" class="old-price">
              {{ formatPrice(product.old_price, product.currency) }}
            </span>
          </div>

          <p v-if="product.availability" class="availability">
            {{ formatAvailability(product.availability) }}
          </p>

          <p v-if="product.description" class="description">
            {{ product.description }}
          </p>

          <div v-if="highlights.length" class="highlights">
            <span v-for="highlight in highlights" :key="highlight">
              {{ highlight }}
            </span>
          </div>

          <a
            class="cta"
            :href="product.affiliate_url"
            target="_blank"
            rel="noopener sponsored nofollow"
            @click="trackOutboundClick"
          >
            Bekijk aanbieding
          </a>
        </div>
      </section>

      <section class="details-band">
        <div class="section-heading">
          <p class="eyebrow">Details</p>
          <h2>Specificaties</h2>
        </div>

        <dl class="spec-grid">
          <div v-for="[label, value] in specs" :key="label">
            <dt>{{ label }}</dt>
            <dd>{{ value }}</dd>
          </div>
        </dl>
      </section>

      <section v-if="relatedProducts.length" class="related">
        <div class="section-heading">
          <p class="eyebrow">Catalog</p>
          <h2>Vergelijkbare producten</h2>
        </div>

        <div class="product-grid">
          <NuxtLink
            v-for="relatedProduct in relatedProducts"
            :key="relatedProduct.id"
            class="product-card"
            :to="`/preview/${site.slug}/products/${relatedProduct.slug}`"
          >
            <div class="card-image">
              <img
                v-if="relatedProduct.image_url"
                :src="relatedProduct.image_url"
                :alt="relatedProduct.title"
              >
              <span v-else>No image</span>
            </div>
            <div class="card-body">
              <p v-if="relatedProduct.brand" class="card-brand">
                {{ relatedProduct.brand }}
              </p>
              <h3>{{ relatedProduct.title }}</h3>
              <p class="card-price">
                {{ formatPrice(relatedProduct.price, relatedProduct.currency) }}
              </p>
            </div>
          </NuxtLink>
        </div>
      </section>

      <section v-if="recentlyViewedProducts.length" class="recently-viewed">
        <div class="section-heading">
          <p class="eyebrow">Historie</p>
          <h2>Onlangs bekeken</h2>
        </div>

        <div class="product-grid">
          <NuxtLink
            v-for="recentProduct in recentlyViewedProducts"
            :key="recentProduct.id"
            class="product-card"
            :to="`/preview/${site.slug}/products/${recentProduct.slug}`"
          >
            <div class="card-image">
              <img
                v-if="recentProduct.image_url"
                :src="recentProduct.image_url"
                :alt="recentProduct.title"
              >
              <span v-else>No image</span>
            </div>
            <div class="card-body">
              <p v-if="recentProduct.brand" class="card-brand">
                {{ recentProduct.brand }}
              </p>
              <h3>{{ recentProduct.title }}</h3>
              <p class="card-price">
                {{ formatPrice(recentProduct.price, recentProduct.currency) }}
              </p>
            </div>
          </NuxtLink>
        </div>
      </section>

      <SiteFooter :site-name="site.name" :site-slug="site.slug" />
    </template>
  </main>
</template>

<style scoped>
.product-page {
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

.preview-bar a {
  color: #ffffff;
  font-weight: 800;
  text-decoration: none;
}

.product-hero {
  display: grid;
  grid-template-columns: minmax(280px, 0.92fr) minmax(320px, 1fr);
  gap: clamp(28px, 5vw, 72px);
  align-items: center;
  padding: clamp(36px, 6vw, 80px) clamp(20px, 4vw, 56px);
  background: #ffffff;
}

.gallery {
  display: grid;
  gap: 14px;
}

.main-image {
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  overflow: hidden;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #e9eee9;
  color: #65726f;
  font-weight: 800;
}

.main-image img,
.card-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.thumb-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(72px, 96px));
  gap: 10px;
}

.thumb-row img {
  aspect-ratio: 1;
  width: 100%;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
  object-fit: contain;
}

.product-copy {
  max-width: 680px;
}

.eyebrow {
  margin: 0 0 12px;
  color: var(--site-eyebrow);
  font-size: 0.78rem;
  font-weight: 850;
  letter-spacing: 0;
  text-transform: uppercase;
}

h1,
h2,
h3,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 12px;
  font-size: clamp(2.3rem, 6vw, 5rem);
  line-height: 0.96;
}

.brand {
  margin-bottom: 24px;
  color: var(--site-primary);
  font-weight: 850;
}

.price-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  align-items: baseline;
  margin-bottom: 10px;
}

.price {
  color: #111827;
  font-size: 2rem;
  font-weight: 900;
}

.old-price {
  color: #75817e;
  font-size: 1.05rem;
  text-decoration: line-through;
}

.availability {
  color: #52615d;
  font-weight: 750;
  text-transform: capitalize;
}

.description {
  max-width: 620px;
  margin: 22px 0;
  color: #33413f;
  font-size: 1.04rem;
  line-height: 1.7;
}

.highlights {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 26px;
}

.highlights span {
  padding: 7px 10px;
  border: 1px solid #cbd8d3;
  border-radius: 999px;
  background: #f5f7f2;
  color: #31413e;
  font-size: 0.88rem;
  font-weight: 750;
}

.cta,
.back-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 0 18px;
  border-radius: 8px;
  background: var(--site-primary);
  color: #ffffff;
  font-weight: 900;
  text-decoration: none;
}

.details-band,
.related,
.recently-viewed {
  padding: clamp(36px, 6vw, 72px) clamp(20px, 4vw, 56px);
}

.details-band {
  background: #f5f7f2;
}

.related {
  background: #ffffff;
}

.recently-viewed {
  background: #f5f7f2;
}

.section-heading {
  margin-bottom: 22px;
}

.section-heading h2 {
  margin: 0;
  font-size: clamp(1.75rem, 4vw, 2.5rem);
}

.spec-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 1px;
  margin: 0;
  overflow: hidden;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #d9e1dd;
}

.spec-grid div {
  min-height: 86px;
  padding: 16px;
  background: #ffffff;
}

.spec-grid dt {
  margin-bottom: 7px;
  color: #63706d;
  font-size: 0.76rem;
  font-weight: 850;
  text-transform: uppercase;
}

.spec-grid dd {
  margin: 0;
  color: #1f2a28;
  font-weight: 750;
  text-transform: capitalize;
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

.card-image {
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  background: #e9eee9;
  color: #65726f;
  font-weight: 800;
}

.card-body {
  padding: 16px;
}

.card-brand {
  margin-bottom: 8px;
  color: var(--site-primary);
  font-size: 0.78rem;
  font-weight: 850;
  text-transform: uppercase;
}

.card-body h3 {
  min-height: 3.2em;
  margin-bottom: 14px;
  font-size: 1rem;
  line-height: 1.35;
}

.card-price {
  margin-bottom: 0;
  font-size: 1.2rem;
  font-weight: 900;
}

.state {
  width: min(760px, calc(100% - 40px));
  margin: 64px auto;
  padding: 32px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
}

@media (max-width: 820px) {
  .preview-bar {
    align-items: start;
    flex-direction: column;
  }

  .product-hero {
    grid-template-columns: 1fr;
  }
}
</style>
