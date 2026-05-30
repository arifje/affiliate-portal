<script setup lang="ts">
const props = defineProps<{
  siteName: string
}>()

const {
  consent,
  hasCookieConsentChoice,
  allowsAnalytics,
  allowsAffiliate,
  allowsPreferences,
  loadCookieConsent,
  saveCookieConsent,
  acceptAllCookieConsent,
  rejectOptionalCookieConsent,
} = useCookieConsent()

const isReady = ref(false)
const isVisible = ref(false)
const showPreferences = ref(false)
const draft = reactive({
  analytics: false,
  affiliate: false,
  preferences: false,
})

function syncDraftFromConsent(): void {
  draft.analytics = allowsAnalytics.value
  draft.affiliate = allowsAffiliate.value
  draft.preferences = allowsPreferences.value
}

function openPreferences(): void {
  syncDraftFromConsent()
  showPreferences.value = true
  isVisible.value = true
}

function acceptAll(): void {
  acceptAllCookieConsent()
  syncDraftFromConsent()
  isVisible.value = false
  showPreferences.value = false
}

function rejectOptional(): void {
  rejectOptionalCookieConsent()
  syncDraftFromConsent()
  isVisible.value = false
  showPreferences.value = false
}

function savePreferences(): void {
  saveCookieConsent({
    analytics: draft.analytics,
    affiliate: draft.affiliate,
    preferences: draft.preferences,
  })
  isVisible.value = false
  showPreferences.value = false
}

function handleOpenCookieSettings(): void {
  openPreferences()
}

onMounted(() => {
  loadCookieConsent()
  syncDraftFromConsent()
  isReady.value = true
  isVisible.value = !hasCookieConsentChoice.value
  window.addEventListener('affiliate-portal:open-cookie-settings', handleOpenCookieSettings)
})

onBeforeUnmount(() => {
  window.removeEventListener('affiliate-portal:open-cookie-settings', handleOpenCookieSettings)
})
</script>

<template>
  <aside
    v-if="isReady && isVisible"
    class="cookie-consent"
    aria-label="Cookie instellingen"
  >
    <div>
      <p class="eyebrow">Cookies</p>
      <h2>Jouw privacy op {{ props.siteName }}</h2>
      <p>
        We gebruiken noodzakelijke opslag om deze website te laten werken. Met jouw toestemming meten we bezoekersstatistieken,
        affiliate klikken en bewaren we voorkeuren zoals onlangs bekeken producten.
      </p>
    </div>

    <div v-if="showPreferences" class="preference-list">
      <label>
        <input type="checkbox" checked disabled>
        <span>
          <strong>Noodzakelijk</strong>
          <small>Altijd actief voor beveiliging, consent opslag en basiswerking.</small>
        </span>
      </label>

      <label>
        <input v-model="draft.analytics" type="checkbox">
        <span>
          <strong>Analytisch</strong>
          <small>Meet bezoekers, productweergaven en prestaties zonder advertentieprofielen.</small>
        </span>
      </label>

      <label>
        <input v-model="draft.affiliate" type="checkbox">
        <span>
          <strong>Affiliate meting</strong>
          <small>Registreert uitgaande klikken zodat partnerprestaties kunnen worden gemeten.</small>
        </span>
      </label>

      <label>
        <input v-model="draft.preferences" type="checkbox">
        <span>
          <strong>Voorkeuren</strong>
          <small>Bewaart functies zoals onlangs bekeken producten op dit apparaat.</small>
        </span>
      </label>
    </div>

    <div class="actions">
      <button type="button" class="ghost" @click="rejectOptional">
        Alleen noodzakelijk
      </button>
      <button v-if="!showPreferences" type="button" class="secondary" @click="openPreferences">
        Voorkeuren
      </button>
      <button v-if="showPreferences" type="button" class="secondary" @click="savePreferences">
        Voorkeuren opslaan
      </button>
      <button type="button" class="primary" @click="acceptAll">
        Alles accepteren
      </button>
    </div>
  </aside>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  right: clamp(14px, 3vw, 28px);
  bottom: clamp(14px, 3vw, 28px);
  z-index: 80;
  display: grid;
  gap: 16px;
  width: min(720px, calc(100vw - 28px));
  padding: 18px;
  border: 1px solid rgba(23, 33, 31, 0.14);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 24px 72px rgba(23, 33, 31, 0.18);
  color: var(--site-text, #17211f);
}

.eyebrow {
  margin: 0 0 8px;
  color: var(--site-eyebrow, var(--site-primary, #0f766e));
  font-size: 0.74rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

h2,
p {
  margin-top: 0;
}

h2 {
  margin-bottom: 8px;
  font-size: clamp(1.2rem, 3vw, 1.55rem);
}

p {
  margin-bottom: 0;
  color: #43534f;
  line-height: 1.55;
}

.preference-list {
  display: grid;
  gap: 10px;
}

.preference-list label {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 12px;
  align-items: start;
  padding: 12px;
  border: 1px solid #d9e1dd;
  border-radius: 8px;
  background: #f8faf7;
}

.preference-list input {
  width: 18px;
  height: 18px;
  margin-top: 2px;
  accent-color: var(--site-primary, #0f766e);
}

.preference-list span,
.preference-list strong,
.preference-list small {
  display: block;
}

.preference-list strong {
  margin-bottom: 4px;
}

.preference-list small {
  color: #5d6a66;
  line-height: 1.45;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: flex-end;
}

button {
  min-height: 42px;
  padding: 0 14px;
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  font-weight: 900;
}

.ghost {
  border: 1px solid #cfd9d5;
  background: #ffffff;
  color: var(--site-text, #17211f);
}

.secondary {
  border: 1px solid var(--site-primary, #0f766e);
  background: #ffffff;
  color: var(--site-primary, #0f766e);
}

.primary {
  border: 1px solid var(--site-primary, #0f766e);
  background: var(--site-primary, #0f766e);
  color: #ffffff;
}

@media (max-width: 640px) {
  .cookie-consent {
    right: 10px;
    bottom: 10px;
    width: calc(100vw - 20px);
  }

  .actions {
    display: grid;
    grid-template-columns: 1fr;
  }
}
</style>
