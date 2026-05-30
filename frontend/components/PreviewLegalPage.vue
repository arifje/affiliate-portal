<script setup lang="ts">
type LegalPageKey = 'privacy' | 'cookies' | 'terms'

type LegalSitePreviewResponse = {
  site: {
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
  }
}

type LegalSection = {
  title: string
  body: string
}

const props = defineProps<{
  page: LegalPageKey
}>()

const route = useRoute()
const config = useRuntimeConfig()
const siteSlug = computed(() => String(route.params.siteSlug))
const apiBase = computed(() => import.meta.server ? config.apiBase : config.public.apiBase)

const { data, error, pending } = await useFetch<LegalSitePreviewResponse>(
  () => `${apiBase.value}/sites/preview/${siteSlug.value}`,
)

const isWebsiteOffline = computed(() => {
  const fetchError = error.value as { status?: number; statusCode?: number } | null

  return (fetchError?.statusCode ?? fetchError?.status) === 503
})
const site = computed(() => data.value?.site)
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

const legalContent = computed(() => {
  const siteName = site.value?.name || 'deze website'
  const domain = site.value?.primary_domain || siteName

  if (props.page === 'cookies') {
    return {
      eyebrow: 'Cookiebeleid',
      title: `Cookiebeleid van ${siteName}`,
      intro: `${siteName} gebruikt cookies en vergelijkbare technieken om de website goed te laten werken, bezoekersstatistieken te meten en affiliate links betrouwbaar te verwerken.`,
      sections: [
        {
          title: 'Wat zijn cookies?',
          body: 'Cookies zijn kleine bestanden die door je browser worden opgeslagen. Ze helpen de website om basisfuncties te onthouden, zoals sessiegegevens, voorkeuren en technische informatie.',
        },
        {
          title: 'Welke cookies gebruiken wij?',
          body: 'Wij gebruiken noodzakelijke opslag voor de werking van de website en consent opslag. Alleen met jouw toestemming gebruiken we analytische opslag, affiliate meting en voorkeuren zoals onlangs bekeken producten.',
        },
        {
          title: 'Affiliate tracking',
          body: 'Wanneer je via een productlink naar een aanbieder gaat, kan de partner of het affiliate netwerk meten dat de klik via deze website kwam. Dit is nodig om eventuele commissies correct toe te wijzen.',
        },
        {
          title: 'Cookies beheren',
          body: 'Je kunt je keuze wijzigen via Cookie-instellingen in de footer. Je kunt cookies en lokale opslag ook verwijderen of blokkeren via je browser. Sommige onderdelen van de website werken dan mogelijk minder goed.',
        },
      ],
    }
  }

  if (props.page === 'terms') {
    return {
      eyebrow: 'Gebruikersvoorwaarden',
      title: `Gebruikersvoorwaarden van ${siteName}`,
      intro: `Door ${domain} te gebruiken, ga je akkoord met deze voorwaarden. De website is bedoeld om producten, categorieen en aanbiedingen overzichtelijk te vergelijken.`,
      sections: [
        {
          title: 'Gebruik van de website',
          body: 'Je mag de website gebruiken voor persoonlijke orientatie en vergelijking. Het is niet toegestaan om de website te misbruiken, te overbelasten of gegevens geautomatiseerd te kopieren zonder toestemming.',
        },
        {
          title: 'Productinformatie',
          body: 'Wij proberen productinformatie actueel en zorgvuldig te tonen. Prijzen, beschikbaarheid, levertijden en productdetails kunnen echter wijzigen bij de aanbieder. Controleer daarom altijd de informatie op de website van de aanbieder voordat je bestelt.',
        },
        {
          title: 'Affiliate links',
          body: 'Sommige links op deze website zijn affiliate links. Als je via zo een link iets koopt, kan de website een commissie ontvangen. Dit heeft geen invloed op de prijs die jij betaalt.',
        },
        {
          title: 'Aansprakelijkheid',
          body: 'Wij zijn niet verantwoordelijk voor aankopen, leveringen, garanties of klantenservice van externe aanbieders. Voor vragen over een bestelling neem je contact op met de betreffende aanbieder.',
        },
      ],
    }
  }

  return {
    eyebrow: 'Privacybeleid',
    title: `Privacybeleid van ${siteName}`,
    intro: `${siteName} gaat zorgvuldig om met bezoekersgegevens. In dit privacybeleid leggen we uit welke gegevens we verwerken en waarom.`,
    sections: [
      {
        title: 'Welke gegevens verwerken wij?',
        body: 'Wij kunnen technische gegevens verwerken, zoals IP-adres in afgeschermde vorm, browserinformatie, bezochte pagina, klikgedrag en een willekeurig bezoekers-ID voor statistieken.',
      },
      {
        title: 'Waarom verwerken wij gegevens?',
        body: 'Deze gegevens gebruiken we om de website goed te laten werken, bezoekersstatistieken bij te houden, fraude te voorkomen en affiliate klikken naar partners te registreren.',
      },
      {
        title: 'Delen met derden',
        body: 'Wanneer je op een affiliate link klikt, word je doorgestuurd naar een externe aanbieder of affiliate netwerk. Die partijen kunnen eigen privacy- en cookievoorwaarden hanteren.',
      },
      {
        title: 'Bewaartermijn en rechten',
        body: 'Wij bewaren gegevens niet langer dan nodig is voor analyse, beveiliging en rapportage. Je kunt contact opnemen als je vragen hebt over inzage, correctie of verwijdering van gegevens.',
      },
    ],
  }
})

const sections = computed<LegalSection[]>(() => legalContent.value.sections)

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
  title: site.value ? `${legalContent.value.eyebrow} | ${site.value.name}` : legalContent.value.eyebrow,
  meta: [
    {
      name: 'robots',
      content: 'index,follow',
    },
  ],
}))
</script>

<template>
  <main class="legal-page" :style="themeStyle">
    <section v-if="pending" class="state">
      <p>Pagina laden...</p>
    </section>

    <section v-else-if="isWebsiteOffline" class="state">
      <p class="eyebrow">Offline</p>
      <h1>Website tijdelijk offline</h1>
      <p>Deze website is momenteel niet publiek beschikbaar.</p>
    </section>

    <section v-else-if="error || !site" class="state">
      <p class="eyebrow">Niet beschikbaar</p>
      <h1>Pagina niet gevonden</h1>
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

      <section class="legal-hero">
        <p class="eyebrow">{{ legalContent.eyebrow }}</p>
        <h1>{{ legalContent.title }}</h1>
        <p>{{ legalContent.intro }}</p>
      </section>

      <section class="legal-content">
        <article
          v-for="section in sections"
          :key="section.title"
        >
          <h2>{{ section.title }}</h2>
          <p>{{ section.body }}</p>
        </article>

        <article>
          <h2>Contact</h2>
          <p>
            Heb je vragen over deze pagina? Neem dan contact op met de beheerder van {{ site.name }}.
          </p>
        </article>
      </section>

      <SiteFooter :site-name="site.name" :site-slug="site.slug" />
      <CookieConsent :site-name="site.name" />
    </template>
  </main>
</template>

<style scoped>
.legal-page {
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
.nav-links a {
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

.legal-hero,
.legal-content {
  padding-right: clamp(20px, 4vw, 56px);
  padding-left: clamp(20px, 4vw, 56px);
}

.legal-hero {
  padding-top: clamp(50px, 8vw, 96px);
  padding-bottom: clamp(34px, 6vw, 68px);
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
p {
  margin-top: 0;
}

h1 {
  max-width: 900px;
  margin-bottom: 18px;
  font-size: clamp(2.4rem, 6vw, 5rem);
  line-height: 0.98;
  overflow-wrap: anywhere;
}

.legal-hero p:not(.eyebrow) {
  max-width: 760px;
  margin-bottom: 0;
  color: #43534f;
  font-size: 1.08rem;
  line-height: 1.65;
}

.legal-content {
  display: grid;
  gap: 18px;
  padding-top: clamp(34px, 5vw, 58px);
  padding-bottom: clamp(42px, 6vw, 76px);
  background: #ffffff;
}

.legal-content article {
  max-width: 860px;
}

.legal-content h2 {
  margin-bottom: 10px;
  font-size: clamp(1.35rem, 3vw, 1.85rem);
}

.legal-content p {
  margin-bottom: 0;
  color: #43534f;
  line-height: 1.75;
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
  .site-header {
    grid-template-columns: 1fr;
  }

  .nav-links {
    justify-content: start;
    overflow-x: auto;
  }
}
</style>
