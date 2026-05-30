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

type SitePreviewCategory = {
  id: number
  name: string
  slug: string
  description: string | null
  products_count: number
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
  featured_products: SitePreviewProduct[]
  categories: SitePreviewCategory[]
}

const route = useRoute()
const config = useRuntimeConfig()
const searchQuery = ref('')

const slug = computed(() => String(route.params.slug))
const apiBase = computed(() => import.meta.server ? config.apiBase : config.public.apiBase)
const { data, error, pending } = await useFetch<SitePreviewResponse>(
  () => `${apiBase.value}/sites/preview/${slug.value}`,
)

const site = computed(() => data.value?.site)
const products = computed(() => data.value?.products ?? [])
const manualFeaturedProducts = computed(() => data.value?.featured_products ?? [])
const categories = computed(() => data.value?.categories ?? [])
const featuredProducts = computed(() => manualFeaturedProducts.value.length
  ? manualFeaturedProducts.value
  : products.value.slice(0, 4))
const saleProducts = computed(() => products.value.filter((product) => product.old_price).slice(0, 4))

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

const variantClass = computed(() => {
  const variant = site.value?.layout?.home_variant || site.value?.layout?.variant || 'clean'

  return `variant-${variant}`
})

const siteLabel = computed(() => site.value?.name.replace(/\.[^.]+$/, '') || 'producten')
const heroTitle = computed(() => site.value?.settings?.hero_title || `Vind de beste ${siteLabel.value}`)
const heroIntro = computed(() => site.value?.settings?.hero_intro || 'Vergelijk zorgvuldig geselecteerde producten, bekijk actuele aanbiedingen en klik direct door naar de winkel.')
const heroBadge = computed(() => site.value?.settings?.hero_badge || 'Onafhankelijke affiliate vergelijking')
const heroImageUrl = computed(() => mediaUrl(site.value?.settings?.hero_image_url || site.value?.settings?.hero_image))
const searchPlaceholder = computed(() => site.value?.settings?.search_placeholder || 'Zoek op product, merk of categorie')
const featuredTitle = computed(() => site.value?.settings?.featured_title || 'Uitgelichte keuzes')
const categoryTitle = computed(() => site.value?.settings?.category_title || 'Shop op categorie')
const footerTagline = computed(() => site.value?.settings?.footer_tagline || 'Een gedeelde frontend, afgestemd per domein.')

const visibleProducts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return products.value.filter((product) => {
    if (!query) {
      return true
    }

    return [
      product.title,
      product.brand,
      product.category?.name,
      product.partner?.name,
      product.availability,
    ].filter(Boolean).some((value) => String(value).toLowerCase().includes(query))
  })
})

const topBrands = computed(() => {
  const brands = products.value
    .map((product) => product.brand)
    .filter((brand): brand is string => Boolean(brand))

  return [...new Set(brands)].slice(0, 8)
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

function productPath(product: SitePreviewProduct): string {
  return `/preview/${site.value?.slug}/products/${product.slug}`
}

function categoryPath(category: SitePreviewCategory): string {
  return `/preview/${site.value?.slug}/categories/${category.slug}`
}

function slugify(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

function brandPath(brand: string): string {
  return `/preview/${site.value?.slug}/brands/${slugify(brand)}`
}

function mediaUrl(path: string | null | undefined): string | null {
  if (!path) {
    return null
  }

  if (/^https?:\/\//.test(path)) {
    return path
  }

  const publicBackendBase = config.public.apiBase.replace(/\/api\/?$/, '')
  const cleanPath = path.replace(/^\/?(storage\/)?/, '')

  return `${publicBackendBase}/storage/${cleanPath}`
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

useHead(() => ({
  title: site.value ? `${site.value.name} | Vergelijk producten` : 'Site preview',
}))
</script>

<template>
  <main class="storefront" :class="variantClass" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Loading preview...</p>
    </section>

    <section v-else-if="error || !site" class="state">
      <p class="eyebrow">Preview unavailable</p>
      <h1>Site not found</h1>
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
        <div class="preview-pill">
          <span>{{ site.is_active ? 'Active' : 'Inactive' }}</span>
          <strong>Preview</strong>
        </div>
      </header>

      <section class="hero" :class="{ 'hero-has-image': heroImageUrl }">
        <div class="hero-copy">
          <p class="eyebrow">{{ heroBadge }}</p>
          <h1>{{ heroTitle }}</h1>
          <p>{{ heroIntro }}</p>

          <form class="search-panel" @submit.prevent="submitSearch">
            <label for="storefront-search">Search</label>
            <div class="search-box">
              <span aria-hidden="true">/</span>
              <input
                id="storefront-search"
                v-model="searchQuery"
                type="search"
                :placeholder="searchPlaceholder"
                autocomplete="off"
              >
              <button type="submit">Zoeken</button>
            </div>
          </form>
        </div>

        <div v-if="heroImageUrl" class="hero-visual">
          <img :src="heroImageUrl" :alt="`${site.name} header image`">
        </div>
      </section>

      <section id="categories" class="category-band">
        <div class="section-heading">
          <p class="eyebrow">Navigatie</p>
          <h2>{{ categoryTitle }}</h2>
        </div>

        <div v-if="categories.length" class="category-grid">
          <NuxtLink
            v-for="category in categories"
            :key="category.id"
            class="category-tile"
            :to="categoryPath(category)"
          >
            <span>{{ category.name }}</span>
            <strong>{{ category.products_count }} producten</strong>
            <small>{{ category.description || 'Bekijk de beste keuzes in deze categorie.' }}</small>
          </NuxtLink>
        </div>
      </section>

      <section id="featured" class="featured-band">
        <div class="section-heading">
          <p class="eyebrow">Aanbevolen</p>
          <h2>{{ featuredTitle }}</h2>
        </div>

        <div v-if="featuredProducts.length" class="feature-layout">
          <NuxtLink class="feature-main" :to="productPath(featuredProducts[0])">
            <img v-if="featuredProducts[0].image_url" :src="featuredProducts[0].image_url" :alt="featuredProducts[0].title">
            <div>
              <p v-if="featuredProducts[0].brand" class="brand">{{ featuredProducts[0].brand }}</p>
              <h3>{{ featuredProducts[0].title }}</h3>
              <p class="feature-price">{{ formatPrice(featuredProducts[0].price, featuredProducts[0].currency) }}</p>
            </div>
          </NuxtLink>

          <div class="feature-list">
            <NuxtLink
              v-for="product in featuredProducts.slice(1)"
              :key="product.id"
              class="feature-row"
              :to="productPath(product)"
            >
              <span v-if="product.brand">{{ product.brand }}</span>
              <strong>{{ product.title }}</strong>
              <em>{{ formatPrice(product.price, product.currency) }}</em>
            </NuxtLink>
          </div>
        </div>
      </section>

      <section v-if="saleProducts.length" class="sale-strip">
        <div>
          <p class="eyebrow">Prijsvoordeel</p>
          <h2>Producten met korting</h2>
        </div>
        <div class="sale-list">
          <NuxtLink
            v-for="product in saleProducts"
            :key="product.id"
            :to="productPath(product)"
          >
            <span>{{ product.brand || product.category?.name }}</span>
            <strong>{{ formatPrice(product.price, product.currency) }}</strong>
          </NuxtLink>
        </div>
      </section>

      <section id="catalog" class="catalog-band">
        <div class="section-heading catalog-heading">
          <div>
            <p class="eyebrow">Catalogus</p>
            <h2>{{ visibleProducts.length }} producten gevonden</h2>
          </div>
          <button v-if="searchQuery" type="button" class="clear-button" @click="searchQuery = ''">
            Reset filters
          </button>
        </div>

        <div v-if="topBrands.length" class="brand-row">
          <span>Merken</span>
          <NuxtLink
            v-for="brand in topBrands"
            :key="brand"
            :to="brandPath(brand)"
          >
            {{ brand }}
          </NuxtLink>
        </div>

        <div v-if="visibleProducts.length" class="product-grid">
          <NuxtLink
            v-for="product in visibleProducts"
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
          <h3>Geen producten gevonden</h3>
          <p>Pas je zoekterm of categorie aan om meer producten te zien.</p>
        </div>
      </section>

      <footer class="site-footer">
        <strong>{{ site.name }}</strong>
        <span>{{ footerTagline }}</span>
      </footer>
    </template>
  </main>
</template>

<style scoped>
.storefront {
  min-height: 100vh;
  color: var(--site-text);
  background: var(--site-bg);
  font-family: var(--site-font);
}

.site-header {
  position: sticky;
  top: 0;
  z-index: 10;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 24px;
  align-items: center;
  padding: 14px clamp(20px, 4vw, 56px);
  border-bottom: 1px solid rgba(23, 33, 31, 0.08);
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(18px);
}

.brand-mark {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  width: fit-content;
  color: var(--site-text);
  text-decoration: none;
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

.brand-mark strong,
.category-tile,
.preview-pill strong {
  font-weight: 900;
}

.nav-links {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.nav-links a,
.preview-pill {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  padding: 0 12px;
  border-radius: 999px;
  color: #31413e;
  font-size: 0.9rem;
  font-weight: 800;
  text-decoration: none;
}

.nav-links a:hover {
  background: var(--site-muted);
}

.preview-pill {
  justify-self: end;
  gap: 8px;
  border: 1px solid #d9e1dd;
  background: #ffffff;
}

.preview-pill span {
  color: var(--site-primary);
}

.hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: clamp(28px, 5vw, 72px);
  align-items: center;
  min-height: 68vh;
  padding: clamp(64px, 9vw, 124px) clamp(20px, 4vw, 56px) clamp(42px, 6vw, 76px);
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(237, 247, 244, 0.62)),
    var(--site-bg);
}

.hero-has-image {
  grid-template-columns: minmax(0, 1fr) minmax(300px, 460px);
}

.hero-copy {
  max-width: 880px;
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
  max-width: 850px;
  margin-bottom: 20px;
  font-size: clamp(3rem, 8vw, 6.8rem);
  line-height: 0.92;
  overflow-wrap: anywhere;
}

.hero-copy > p:not(.eyebrow) {
  max-width: 680px;
  margin-bottom: 30px;
  color: #43534f;
  font-size: clamp(1rem, 2vw, 1.22rem);
  line-height: 1.65;
}

.search-panel {
  width: min(720px, 100%);
}

.search-panel label {
  display: block;
  margin-bottom: 9px;
  font-size: 0.82rem;
  font-weight: 900;
  text-transform: uppercase;
}

.search-box {
  min-height: 64px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 0 10px 0 18px;
  border: 1px solid #cfd9d5;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(23, 33, 31, 0.08);
}

.search-box span {
  color: var(--site-primary);
  font-size: 1.4rem;
  font-weight: 900;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  color: var(--site-text);
  font: inherit;
  font-size: 1.05rem;
}

.search-box button {
  min-height: 44px;
  padding: 0 16px;
  border: 0;
  border-radius: 8px;
  background: var(--site-primary);
  color: #ffffff;
  cursor: pointer;
  font: inherit;
  font-weight: 900;
}

.search-box button:hover {
  background: var(--site-primary-dark);
}

.hero-visual {
  min-height: 390px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--site-muted);
  box-shadow: 0 24px 70px rgba(23, 33, 31, 0.16);
}

.hero-visual img {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 390px;
  object-fit: cover;
}

.variant-compact .hero-visual,
.variant-compact .hero-visual img {
  min-height: 300px;
}

.category-band,
.featured-band,
.catalog-band,
.sale-strip {
  padding: clamp(40px, 6vw, 76px) clamp(20px, 4vw, 56px);
}

.category-band,
.catalog-band {
  background: #ffffff;
}

.featured-band {
  background: var(--site-bg);
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
  font-size: clamp(1.9rem, 4vw, 3rem);
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 14px;
}

.category-tile {
  display: block;
  min-height: 160px;
  padding: 18px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
  color: var(--site-text);
  text-align: left;
  text-decoration: none;
}

.category-tile:hover {
  border-color: var(--site-primary);
  background: var(--site-soft);
}

.category-tile span,
.category-tile strong,
.category-tile small {
  display: block;
}

.category-tile span {
  margin-bottom: 10px;
  color: var(--site-primary);
  font-size: 1.08rem;
  font-weight: 900;
}

.category-tile strong {
  margin-bottom: 14px;
  font-size: 0.86rem;
}

.category-tile small {
  color: #5c6b67;
  font-size: 0.88rem;
  line-height: 1.5;
}

.feature-layout {
  display: grid;
  grid-template-columns: minmax(280px, 1.2fr) minmax(260px, 0.8fr);
  gap: 18px;
}

.feature-main,
.feature-row,
.product-card,
.sale-list a {
  color: inherit;
  text-decoration: none;
}

.feature-main {
  display: grid;
  grid-template-columns: minmax(220px, 0.82fr) 1fr;
  gap: 22px;
  align-items: center;
  padding: clamp(18px, 3vw, 28px);
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
}

.feature-main img {
  width: 100%;
  aspect-ratio: 4 / 3;
  border-radius: 8px;
  background: var(--site-muted);
  object-fit: contain;
}

.feature-main h3 {
  margin-bottom: 16px;
  font-size: clamp(1.6rem, 4vw, 3rem);
  line-height: 1.02;
}

.feature-price {
  margin-bottom: 0;
  font-size: 1.7rem;
  font-weight: 900;
}

.feature-list {
  display: grid;
  gap: 12px;
}

.feature-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px 16px;
  align-items: center;
  padding: 18px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
}

.feature-row span,
.brand {
  color: var(--site-primary);
  font-size: 0.76rem;
  font-weight: 900;
  text-transform: uppercase;
}

.feature-row strong {
  line-height: 1.35;
}

.feature-row em {
  grid-row: span 2;
  color: var(--site-text);
  font-style: normal;
  font-weight: 900;
}

.sale-strip {
  display: grid;
  grid-template-columns: minmax(220px, 320px) 1fr;
  gap: 24px;
  align-items: center;
  background: var(--site-primary-dark);
  color: #ffffff;
}

.sale-strip .eyebrow {
  color: #ffdda3;
}

.sale-strip h2 {
  margin-bottom: 0;
  font-size: clamp(1.8rem, 4vw, 3rem);
}

.sale-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
}

.sale-list a {
  padding: 14px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.09);
}

.sale-list span,
.sale-list strong {
  display: block;
}

.sale-list span {
  margin-bottom: 6px;
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.78rem;
  font-weight: 850;
}

.sale-list strong {
  font-size: 1.2rem;
}

.catalog-heading {
  align-items: center;
}

.clear-button,
.brand-row a {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  padding: 0 12px;
  border: 1px solid #cfd9d5;
  border-radius: 999px;
  background: #ffffff;
  color: var(--site-text);
  font: inherit;
  font-size: 0.88rem;
  font-weight: 850;
  text-decoration: none;
}

.clear-button {
  cursor: pointer;
}

.brand-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-bottom: 24px;
}

.brand-row span {
  margin-right: 4px;
  color: #65726f;
  font-size: 0.84rem;
  font-weight: 900;
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

.product-card:hover,
.feature-main:hover,
.feature-row:hover {
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

.site-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  align-items: center;
  justify-content: space-between;
  padding: 26px clamp(20px, 4vw, 56px);
  color: rgba(255, 255, 255, 0.76);
  background: #17211f;
}

.site-footer strong {
  color: #ffffff;
}

.variant-compact .hero {
  min-height: 52vh;
}

.variant-bold .hero {
  background:
    linear-gradient(135deg, rgba(19, 78, 74, 0.92), rgba(23, 33, 31, 0.86)),
    var(--site-primary);
  color: #ffffff;
}

.variant-bold .hero-copy > p:not(.eyebrow) {
  color: rgba(255, 255, 255, 0.78);
}

@media (max-width: 900px) {
  .site-header,
  .hero,
  .feature-layout,
  .feature-main,
  .sale-strip {
    grid-template-columns: 1fr;
  }

  .site-header {
    position: static;
  }

  .nav-links {
    justify-content: start;
    overflow-x: auto;
  }

  .preview-pill {
    justify-self: start;
  }

  .hero {
    min-height: auto;
  }
}

@media (max-width: 640px) {
  .section-heading,
  .catalog-heading {
    align-items: start;
    flex-direction: column;
  }

  .nav-links {
    width: 100%;
  }

  .search-box {
    grid-template-columns: auto 1fr;
    min-height: 56px;
    padding-bottom: 12px;
  }

  .search-box span,
  .search-box input {
    padding-top: 12px;
  }

  .search-box button {
    grid-column: 1 / -1;
  }
}
</style>
