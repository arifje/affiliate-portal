<script setup lang="ts">
type PreviewProduct = {
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
  category: { id: number; name: string; slug: string } | null
}

type PreviewCategory = {
  id: number
  name: string
  slug: string
  description: string | null
  products_count: number
}

type PreviewBrand = {
  name: string
  slug: string
  products_count: number
}

type PreviewSite = {
  id: number
  name: string
  slug: string
  primary_domain: string
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

type PreviewProductIndexResponse = {
  site: PreviewSite
  products: PreviewProduct[]
  categories: PreviewCategory[]
  brands: PreviewBrand[]
  meta: {
    title: string
    search: string
    category: { id: number; name: string; slug: string; description: string | null } | null
    brand: { name: string; slug: string } | null
    deals: boolean
    sort: string
  }
}

const props = defineProps<{
  siteSlug: string
  mode: 'products' | 'categories' | 'deals' | 'search' | 'category' | 'brand'
  categorySlug?: string
  brandSlug?: string
}>()

const route = useRoute()
const config = useRuntimeConfig()
const searchQuery = ref(String(route.query.q || ''))
const sort = ref(String(route.query.sort || 'latest'))
const apiBase = computed(() => import.meta.server ? config.apiBase : config.public.apiBase)

const requestUrl = computed(() => {
  const params = new URLSearchParams()

  if (props.mode === 'deals') {
    params.set('deals', '1')
  }

  if (props.mode === 'category' && props.categorySlug) {
    params.set('category', props.categorySlug)
  }

  if (props.mode === 'brand' && props.brandSlug) {
    params.set('brand', props.brandSlug)
  }

  if (props.mode === 'search' && route.query.q) {
    params.set('q', String(route.query.q))
  }

  if (sort.value !== 'latest') {
    params.set('sort', sort.value)
  }

  const query = params.toString()

  return `${apiBase.value}/sites/preview/${props.siteSlug}/products${query ? `?${query}` : ''}`
})

const { data, error, pending } = await useFetch<PreviewProductIndexResponse>(
  () => requestUrl.value,
  {
    watch: [requestUrl],
  },
)

const isWebsiteOffline = computed(() => {
  const fetchError = error.value as { status?: number; statusCode?: number } | null

  return (fetchError?.statusCode ?? fetchError?.status) === 503
})
const site = computed(() => data.value?.site)
const products = computed(() => data.value?.products ?? [])
const categories = computed(() => data.value?.categories ?? [])
const brands = computed(() => data.value?.brands ?? [])
const meta = computed(() => data.value?.meta)
const pageTitle = computed(() => props.mode === 'categories' ? 'Categorieen' : meta.value?.title)
let stopSiteVisitHeartbeat: (() => void) | null = null

const themeStyle = computed(() => {
  const theme = site.value?.theme ?? {}

  return {
    '--site-primary': theme.primary_color || theme.primary || '#0f766e',
    '--site-primary-dark': theme.primary_dark || '#134e4a',
    '--site-accent': theme.accent_color || theme.accent || '#d97706',
    '--site-eyebrow': theme.eyebrow_color || theme.accent_color || theme.accent || '#d97706',
    '--site-bg': theme.background_color || theme.background || '#f6f8f4',
    '--site-muted': theme.muted_color || theme.muted || '#e7eee9',
    '--site-surface': theme.surface_color || theme.surface || '#ffffff',
    '--site-text': theme.text_color || theme.text || '#17211f',
    '--site-soft': theme.soft_color || theme.soft || '#edf7f4',
    '--site-font': theme.font_family || theme.font || 'Inter, ui-sans-serif, system-ui, sans-serif',
  }
})

const subtitle = computed(() => {
  if (props.mode === 'search') {
    return meta.value?.search
      ? `Resultaten voor "${meta.value.search}"`
      : 'Gebruik de zoekbalk om producten, merken en categorieen te vinden.'
  }

  if (props.mode === 'categories') {
    return 'Kies een categorie of gebruik de filters om direct naar de juiste productgroep te gaan.'
  }

  if (props.mode === 'category') {
    return meta.value?.category?.description || 'Alle producten binnen deze categorie.'
  }

  if (props.mode === 'brand') {
    return `Alle producten van ${meta.value?.brand?.name || 'dit merk'}.`
  }

  if (props.mode === 'deals') {
    return 'Producten met een zichtbare oude prijs en actuele korting.'
  }

  return 'Alle actieve producten binnen deze site.'
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

function submitSearch(): void {
  const query = searchQuery.value.trim()

  if (!query || !site.value) {
    return
  }

  navigateTo({
    path: `/preview/${site.value.slug}/search`,
    query: { q: query },
  })
}

function productPath(product: PreviewProduct): string {
  return `/preview/${site.value?.slug}/products/${product.slug}`
}

watch(sort, () => {
  if (!site.value) {
    return
  }

  const query = { ...route.query }

  if (sort.value === 'latest') {
    delete query.sort
  } else {
    query.sort = sort.value
  }

  navigateTo({ path: route.path, query })
})

onMounted(() => {
  watch(site, (currentSite) => {
    stopSiteVisitHeartbeat?.()
    stopSiteVisitHeartbeat = currentSite ? startSiteVisitHeartbeat(currentSite.slug) : null
  }, { immediate: true })
})

onBeforeUnmount(() => {
  stopSiteVisitHeartbeat?.()
})

useHead(() => ({
  title: site.value && pageTitle.value ? `${pageTitle.value} | ${site.value.name}` : 'Producten',
  meta: [
    {
      name: 'robots',
      content: props.mode === 'search' ? 'noindex,follow' : 'index,follow',
    },
  ],
}))
</script>

<template>
  <main class="listing-page" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Loading products...</p>
    </section>

    <section v-else-if="isWebsiteOffline" class="state">
      <p class="eyebrow">Offline</p>
      <h1>Website tijdelijk offline</h1>
      <p>Deze website is momenteel niet publiek beschikbaar.</p>
    </section>

    <section v-else-if="error || !site || !meta" class="state">
      <p class="eyebrow">Preview unavailable</p>
      <h1>Page not found</h1>
    </section>

    <template v-else>
      <header class="site-header">
        <NuxtLink class="brand-mark" :to="`/preview/${site.slug}`">
          <span>{{ site.name.slice(0, 1).toUpperCase() }}</span>
          <strong>{{ site.name }}</strong>
        </NuxtLink>
        <nav class="nav-links" aria-label="Main navigation">
          <NuxtLink :to="`/preview/${site.slug}/categories`">Categorieen</NuxtLink>
          <NuxtLink :to="`/preview/${site.slug}/deals`">Aanbiedingen</NuxtLink>
          <NuxtLink :to="`/preview/${site.slug}/products`">Producten</NuxtLink>
        </nav>
      </header>

      <section class="listing-hero">
        <div>
          <p class="eyebrow">{{ site.primary_domain }}</p>
          <h1>{{ pageTitle }}</h1>
          <p>{{ subtitle }}</p>
        </div>

        <form class="search-panel" @submit.prevent="submitSearch">
          <label for="listing-search">Search</label>
          <div class="search-box">
            <span aria-hidden="true">/</span>
            <input
              id="listing-search"
              v-model="searchQuery"
              type="search"
              :placeholder="site.settings.search_placeholder || 'Zoek op product, merk of categorie'"
              autocomplete="off"
            >
            <button type="submit">Zoeken</button>
          </div>
        </form>
      </section>

      <section class="content-shell">
        <aside class="filters">
          <section>
            <h2>Categorieen</h2>
            <NuxtLink
              v-for="category in categories"
              :key="category.id"
              :to="`/preview/${site.slug}/categories/${category.slug}`"
            >
              <span>{{ category.name }}</span>
              <strong>{{ category.products_count }}</strong>
            </NuxtLink>
          </section>

          <section v-if="brands.length">
            <h2>Merken</h2>
            <NuxtLink
              v-for="brand in brands"
              :key="brand.slug"
              :to="`/preview/${site.slug}/brands/${brand.slug}`"
            >
              <span>{{ brand.name }}</span>
              <strong>{{ brand.products_count }}</strong>
            </NuxtLink>
          </section>
        </aside>

        <section class="results">
          <div class="results-toolbar">
            <p>{{ products.length }} producten</p>
            <select v-model="sort" aria-label="Sort products">
              <option value="latest">Nieuwste eerst</option>
              <option value="price_asc">Prijs oplopend</option>
              <option value="price_desc">Prijs aflopend</option>
            </select>
          </div>

          <div v-if="products.length" class="product-grid">
            <NuxtLink
              v-for="product in products"
              :key="product.id"
              class="product-card"
              :to="productPath(product)"
            >
              <div class="product-image">
                <img v-if="product.image_url" :src="product.image_url" :alt="product.title">
                <span v-else>No image</span>
              </div>
              <div class="product-body">
                <p v-if="product.brand" class="brand">{{ product.brand }}</p>
                <h3>{{ product.title }}</h3>
                <div class="price-row">
                  <p class="price">{{ formatPrice(product.price, product.currency) }}</p>
                  <p v-if="product.old_price" class="old-price">{{ formatPrice(product.old_price, product.currency) }}</p>
                </div>
                <p v-if="product.availability" class="availability">
                  {{ product.availability.replaceAll('_', ' ') }}
                </p>
              </div>
            </NuxtLink>
          </div>

          <div v-else class="empty">
            <h2>Geen producten gevonden</h2>
            <p>Probeer een andere zoekterm, categorie of sortering.</p>
          </div>
        </section>
      </section>
    </template>
  </main>
</template>

<style scoped>
.listing-page {
  min-height: 100vh;
  color: var(--site-text);
  background: var(--site-bg);
  font-family: var(--site-font);
}

.site-header {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 24px;
  align-items: center;
  padding: 14px clamp(20px, 4vw, 56px);
  border-bottom: 1px solid rgba(23, 33, 31, 0.08);
  background: rgba(255, 255, 255, 0.94);
}

.brand-mark,
.nav-links a,
.filters a,
.product-card {
  color: inherit;
  text-decoration: none;
}

.brand-mark {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  width: fit-content;
}

.brand-mark span {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--site-primary);
  color: #ffffff;
  font-weight: 900;
}

.brand-mark strong {
  font-weight: 900;
}

.nav-links {
  display: flex;
  gap: 8px;
  justify-content: end;
}

.nav-links a {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  padding: 0 12px;
  border-radius: 999px;
  color: #31413e;
  font-size: 0.9rem;
  font-weight: 850;
}

.nav-links a:hover {
  background: var(--site-muted);
}

.listing-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 520px);
  gap: clamp(28px, 5vw, 72px);
  align-items: end;
  padding: clamp(46px, 8vw, 92px) clamp(20px, 4vw, 56px);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(237, 247, 244, 0.66)),
    var(--site-bg);
}

.eyebrow {
  margin: 0 0 12px;
  color: var(--site-eyebrow);
  font-size: 0.78rem;
  font-weight: 900;
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
  max-width: 780px;
  margin-bottom: 14px;
  font-size: clamp(2.6rem, 6vw, 5.4rem);
  line-height: 0.96;
  overflow-wrap: anywhere;
}

.listing-hero p:not(.eyebrow) {
  max-width: 650px;
  margin-bottom: 0;
  color: #43534f;
  font-size: 1.08rem;
  line-height: 1.65;
}

.search-panel label {
  display: block;
  margin-bottom: 9px;
  font-size: 0.82rem;
  font-weight: 900;
  text-transform: uppercase;
}

.search-box {
  min-height: 58px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 0 10px 0 16px;
  border: 1px solid #cfd9d5;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(23, 33, 31, 0.08);
}

.search-box span {
  color: var(--site-primary);
  font-size: 1.35rem;
  font-weight: 900;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  color: var(--site-text);
  font: inherit;
}

.search-box button {
  min-height: 40px;
  padding: 0 15px;
  border: 0;
  border-radius: 8px;
  background: var(--site-primary);
  color: #ffffff;
  cursor: pointer;
  font: inherit;
  font-weight: 900;
}

.content-shell {
  display: grid;
  grid-template-columns: minmax(220px, 280px) 1fr;
  gap: 28px;
  padding: clamp(36px, 6vw, 72px) clamp(20px, 4vw, 56px);
}

.filters {
  display: grid;
  align-content: start;
  gap: 18px;
}

.filters section {
  padding: 16px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
}

.filters h2 {
  margin-bottom: 12px;
  font-size: 1rem;
}

.filters a {
  display: flex;
  gap: 12px;
  justify-content: space-between;
  padding: 9px 0;
  border-top: 1px solid #edf1ef;
  font-size: 0.92rem;
  font-weight: 800;
}

.filters a:first-of-type {
  border-top: 0;
}

.filters strong {
  color: var(--site-primary);
}

.results-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.results-toolbar p {
  margin-bottom: 0;
  font-weight: 900;
}

.results-toolbar select {
  min-height: 38px;
  padding: 0 10px;
  border: 1px solid #cfd9d5;
  border-radius: 8px;
  background: #ffffff;
  color: var(--site-text);
  font: inherit;
  font-size: 0.92rem;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 18px;
}

.product-card {
  display: block;
  overflow: hidden;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: var(--site-surface);
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
  background: var(--site-muted);
  color: #65726f;
  font-weight: 800;
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
  font-size: 0.76rem;
  font-weight: 900;
  text-transform: uppercase;
}

.product-body h3 {
  min-height: 3.3em;
  margin-bottom: 14px;
  font-size: 1rem;
  line-height: 1.35;
}

.price-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: baseline;
}

.price {
  margin-bottom: 6px;
  font-size: 1.2rem;
  font-weight: 900;
}

.old-price {
  margin-bottom: 6px;
  color: #75817e;
  font-size: 0.9rem;
  text-decoration: line-through;
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

@media (max-width: 900px) {
  .site-header,
  .listing-hero,
  .content-shell {
    grid-template-columns: 1fr;
  }

  .nav-links {
    justify-content: start;
    overflow-x: auto;
  }
}

@media (max-width: 640px) {
  .search-box {
    grid-template-columns: auto 1fr;
    padding: 0 14px 14px;
  }

  .search-box span {
    padding-top: 14px;
  }

  .search-box input {
    padding-top: 14px;
  }

  .search-box button {
    grid-column: 1 / -1;
  }
}
</style>
