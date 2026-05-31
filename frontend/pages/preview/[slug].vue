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

const slug = computed(() => String(route.params.slug))
const apiBase = computed(() => import.meta.server ? config.apiBase : config.public.apiBase)
const { data, error, pending } = await useFetch<SitePreviewResponse>(
  () => `${apiBase.value}/sites/preview/${slug.value}`,
)

const isWebsiteOffline = computed(() => {
  const fetchError = error.value as { status?: number; statusCode?: number } | null

  return (fetchError?.statusCode ?? fetchError?.status) === 503
})
const site = computed(() => data.value?.site)
const products = computed(() => data.value?.products ?? [])
const manualFeaturedProducts = computed(() => data.value?.featured_products ?? [])
const categories = computed(() => data.value?.categories ?? [])
const featuredProducts = computed(() => {
  const source = manualFeaturedProducts.value.length ? manualFeaturedProducts.value : products.value

  return source.slice(0, 10)
})
const saleProducts = computed(() => products.value.filter((product) => product.old_price).slice(0, 10))
const latestProducts = computed(() => products.value.slice(0, 10))
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

const variantClass = computed(() => {
  const variant = site.value?.layout?.home_variant || site.value?.layout?.variant || 'clean'

  return `variant-${variant}`
})

const siteLabel = computed(() => site.value?.name.replace(/\.[^.]+$/, '') || 'producten')
const heroTitle = computed(() => site.value?.settings?.hero_title || `Vind de beste ${siteLabel.value}`)
const heroIntro = computed(() => site.value?.settings?.hero_intro || 'Vergelijk zorgvuldig geselecteerde producten, bekijk actuele aanbiedingen en klik direct door naar de winkel.')
const heroBadge = computed(() => site.value?.settings?.hero_badge || 'Onafhankelijke affiliate vergelijking')
const heroImageUrl = computed(() => mediaUrl(site.value?.settings?.hero_image_url || site.value?.settings?.hero_image))
const heroStyle = computed(() => {
  if (!heroImageUrl.value) {
    return {}
  }

  const imageUrl = heroImageUrl.value.replaceAll('"', '\\"')

  return {
    backgroundImage: `linear-gradient(90deg, rgba(11, 18, 17, 0.78), rgba(11, 18, 17, 0.5) 48%, rgba(11, 18, 17, 0.2)), url("${imageUrl}")`,
  }
})
const searchPlaceholder = computed(() => site.value?.settings?.search_placeholder || 'Zoek op product, merk of categorie')
const featuredTitle = computed(() => site.value?.settings?.featured_title || 'Uitgelichte keuzes')
const categoryTitle = computed(() => site.value?.settings?.category_title || 'Shop op categorie')
const carouselArrowButton = {
  color: 'neutral',
  variant: 'solid',
  size: 'md',
} as const
const carouselBaseUi = {
  root: 'storefront-carousel',
  viewport: 'overflow-hidden',
  container: 'items-stretch',
  controls: 'pointer-events-none absolute inset-y-0 left-0 right-0',
  prev: 'pointer-events-auto absolute left-0 top-1/2 z-[2] hidden -translate-y-1/2 rounded-full bg-white/95 text-gray-950 shadow-lg ring-1 ring-black/10 hover:bg-white disabled:invisible aria-disabled:invisible sm:inline-flex',
  next: 'pointer-events-auto absolute right-0 top-1/2 z-[2] hidden -translate-y-1/2 rounded-full bg-white/95 text-gray-950 shadow-lg ring-1 ring-black/10 hover:bg-white disabled:invisible aria-disabled:invisible sm:inline-flex',
}
const categoryCarouselUi = {
  ...carouselBaseUi,
  item: 'min-w-0 basis-[84%] pe-4 sm:basis-[48%] lg:basis-[32%] xl:basis-[25%]',
}
const productCarouselUi = {
  ...carouselBaseUi,
  item: 'min-w-0 basis-[84%] pe-4 sm:basis-[44%] lg:basis-[30%] xl:basis-[24%]',
}
const saleCarouselUi = {
  ...carouselBaseUi,
  item: 'min-w-0 basis-[88%] pe-4 md:basis-[50%] xl:basis-[34%]',
  prev: 'pointer-events-auto absolute left-0 top-1/2 z-[2] hidden -translate-y-1/2 rounded-full bg-white text-gray-950 shadow-lg ring-1 ring-white/30 hover:bg-white disabled:invisible aria-disabled:invisible sm:inline-flex',
  next: 'pointer-events-auto absolute right-0 top-1/2 z-[2] hidden -translate-y-1/2 rounded-full bg-white text-gray-950 shadow-lg ring-1 ring-white/30 hover:bg-white disabled:invisible aria-disabled:invisible sm:inline-flex',
}

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
  title: site.value ? `${site.value.name} | Vergelijk producten` : 'Site preview',
}))
</script>

<template>
  <main class="storefront" :class="variantClass" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Loading preview...</p>
    </section>

    <section v-else-if="isWebsiteOffline" class="state">
      <p class="eyebrow">Offline</p>
      <h1>Website tijdelijk offline</h1>
      <p>Deze website is momenteel niet publiek beschikbaar.</p>
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
      </header>

      <section class="hero" :class="{ 'hero-has-image': heroImageUrl }" :style="heroStyle">
        <div class="hero-copy">
          <p class="eyebrow">{{ heroBadge }}</p>
          <h1>{{ heroTitle }}</h1>
          <p>{{ heroIntro }}</p>

          <SiteSearchAutocomplete
            input-id="storefront-search"
            :site-slug="site.slug"
            :placeholder="searchPlaceholder"
            :contrast="Boolean(heroImageUrl)"
          />
        </div>
      </section>

      <section id="categories" class="category-band">
        <div class="section-heading">
          <h2>{{ categoryTitle }}</h2>
        </div>

        <UCarousel
          v-if="categories.length"
          v-slot="{ item: category }"
          arrows
          wheel-gestures
          align="start"
          :slides-to-scroll="'auto'"
          :items="categories"
          :prev="carouselArrowButton"
          :next="carouselArrowButton"
          :ui="categoryCarouselUi"
        >
          <NuxtLink
            :key="category.id"
            class="category-tile"
            :to="categoryPath(category)"
          >
            <span>{{ category.name }}</span>
            <strong>{{ category.products_count }} producten</strong>
            <small>{{ category.description || 'Bekijk de beste keuzes in deze categorie.' }}</small>
          </NuxtLink>
        </UCarousel>
      </section>

      <section id="featured" class="featured-band">
        <div class="section-heading">
          <h2>{{ featuredTitle }}</h2>
        </div>

        <UCarousel
          v-if="featuredProducts.length"
          v-slot="{ item: product }"
          :arrows="featuredProducts.length > 3"
          wheel-gestures
          align="start"
          :slides-to-scroll="'auto'"
          :items="featuredProducts"
          :prev="carouselArrowButton"
          :next="carouselArrowButton"
          :ui="productCarouselUi"
        >
          <NuxtLink
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
            </div>
          </NuxtLink>
        </UCarousel>
      </section>

      <section v-if="saleProducts.length" class="sale-strip">
        <div class="section-heading">
          <h2>Producten met korting</h2>
        </div>

        <UCarousel
          v-slot="{ item: product }"
          class="sale-list"
          arrows
          wheel-gestures
          align="start"
          :slides-to-scroll="'auto'"
          :items="saleProducts"
          :prev="carouselArrowButton"
          :next="carouselArrowButton"
          :ui="saleCarouselUi"
        >
          <NuxtLink
            :key="product.id"
            class="sale-card"
            :to="productPath(product)"
          >
            <span class="sale-thumb">
              <img v-if="product.image_url" :src="product.image_url" :alt="product.title">
              <span v-else>No image</span>
            </span>
            <span class="sale-copy">
              <small>{{ product.brand || product.category?.name }}</small>
              <strong>{{ product.title }}</strong>
              <em>{{ formatPrice(product.price, product.currency) }}</em>
            </span>
          </NuxtLink>
        </UCarousel>
      </section>

      <section id="catalog" class="catalog-band">
        <div class="section-heading">
          <h2>Latest products</h2>
        </div>

        <UCarousel
          v-if="latestProducts.length"
          v-slot="{ item: product }"
          arrows
          wheel-gestures
          align="start"
          :slides-to-scroll="'auto'"
          :items="latestProducts"
          :prev="carouselArrowButton"
          :next="carouselArrowButton"
          :ui="productCarouselUi"
        >
          <NuxtLink
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
        </UCarousel>

        <div v-else class="empty">
          <h3>Geen producten gevonden</h3>
          <p>Er zijn nog geen producten beschikbaar voor deze site.</p>
        </div>
      </section>

      <SiteFooter :site-name="site.name" :site-slug="site.slug" />
      <CookieConsent :site-name="site.name" />
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
  grid-template-columns: 1fr auto;
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
.category-tile {
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
  font-weight: 800;
  text-decoration: none;
}

.nav-links a:hover {
  background: var(--site-muted);
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
  min-height: 72vh;
  color: #ffffff;
  background-color: var(--site-primary-dark);
  background-position: center;
  background-size: cover;
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

.hero-has-image .eyebrow {
  color: #ffdda3;
}

.hero-has-image .hero-copy > p:not(.eyebrow) {
  color: rgba(255, 255, 255, 0.84);
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
  margin-bottom: 22px;
  text-align: left;
}

.section-heading h2 {
  margin: 0;
  font-size: clamp(1.9rem, 4vw, 3rem);
  font-weight: 850;
  line-height: 1.05;
}

:deep(.storefront-carousel) {
  padding-inline: 2px;
}

.category-tile {
  display: block;
  height: 100%;
  min-height: 160px;
  padding: 18px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #ffffff;
  color: var(--site-text);
  text-align: left;
  text-decoration: none;
  transition: background 160ms ease, border-color 160ms ease, transform 160ms ease;
}

.category-tile:hover {
  border-color: var(--site-primary);
  background: var(--site-soft);
  transform: translateY(-2px);
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

.product-card,
.sale-card {
  color: inherit;
  text-decoration: none;
}

.brand {
  color: var(--site-primary);
  font-size: 0.76rem;
  font-weight: 900;
  text-transform: uppercase;
}

.sale-strip {
  background: var(--site-primary-dark);
  color: #ffffff;
}

.sale-strip h2 {
  margin-bottom: 0;
  font-size: clamp(1.8rem, 4vw, 3rem);
}

.sale-list {
  margin-inline: -2px;
}

.sale-card {
  display: grid;
  grid-template-columns: 88px 1fr;
  gap: 14px;
  align-items: center;
  height: 100%;
  padding: 12px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.09);
  transition: background 160ms ease, border-color 160ms ease, transform 160ms ease;
}

.sale-thumb {
  display: grid;
  place-items: center;
  width: 88px;
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.92);
  color: #65726f;
  font-size: 0.72rem;
  font-weight: 850;
}

.sale-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.sale-copy {
  display: grid;
  gap: 5px;
  min-width: 0;
}

.sale-copy small {
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.74rem;
  font-weight: 900;
  text-transform: uppercase;
}

.sale-copy strong {
  overflow: hidden;
  font-size: 0.96rem;
  line-height: 1.32;
  text-overflow: ellipsis;
}

.sale-copy em {
  font-style: normal;
  font-weight: 900;
  font-size: 1.15rem;
}

.product-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: var(--site-surface);
  transition: border-color 160ms ease, transform 160ms ease;
}

.product-card:hover,
.sale-card:hover {
  border-color: var(--site-primary);
  transform: translateY(-2px);
}

.sale-card:hover {
  border-color: rgba(255, 255, 255, 0.38);
  background: rgba(255, 255, 255, 0.14);
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
  display: flex;
  flex: 1;
  flex-direction: column;
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
  margin-top: auto;
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

.variant-compact .hero {
  min-height: 52vh;
}

.variant-bold .hero:not(.hero-has-image) {
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
  .hero {
    grid-template-columns: 1fr;
  }

  .site-header {
    position: static;
  }

  .nav-links {
    justify-content: start;
    overflow-x: auto;
  }

  .hero {
    min-height: auto;
  }
}

@media (max-width: 640px) {
  .nav-links {
    width: 100%;
  }

  .sale-card {
    grid-template-columns: 76px 1fr;
  }

  .sale-thumb {
    width: 76px;
  }

}
</style>
