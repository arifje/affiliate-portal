export type CookieConsentPreferences = {
  necessary: true
  analytics: boolean
  affiliate: boolean
  preferences: boolean
  decidedAt: string
  version: number
}

export type CookieConsentDraft = Pick<CookieConsentPreferences, 'analytics' | 'affiliate' | 'preferences'>

const cookieConsentStorageKey = 'affiliate_portal_cookie_consent'
const cookieConsentVersion = 1

function defaultCookieConsent(): CookieConsentPreferences {
  return {
    necessary: true,
    analytics: false,
    affiliate: false,
    preferences: false,
    decidedAt: '',
    version: cookieConsentVersion,
  }
}

function normalizeCookieConsent(value: unknown): CookieConsentPreferences | null {
  if (!value || typeof value !== 'object') {
    return null
  }

  const candidate = value as Partial<CookieConsentPreferences>

  if (candidate.version !== cookieConsentVersion || typeof candidate.decidedAt !== 'string') {
    return null
  }

  return {
    necessary: true,
    analytics: Boolean(candidate.analytics),
    affiliate: Boolean(candidate.affiliate),
    preferences: Boolean(candidate.preferences),
    decidedAt: candidate.decidedAt,
    version: cookieConsentVersion,
  }
}

export function useCookieConsent() {
  const consent = useState<CookieConsentPreferences | null>('cookie-consent', () => null)
  const hasLoadedCookieConsent = useState('cookie-consent-loaded', () => false)

  const loadCookieConsent = (): void => {
    if (import.meta.server || hasLoadedCookieConsent.value) {
      return
    }

    hasLoadedCookieConsent.value = true

    try {
      const storedConsent = window.localStorage.getItem(cookieConsentStorageKey)

      if (!storedConsent) {
        consent.value = null

        return
      }

      consent.value = normalizeCookieConsent(JSON.parse(storedConsent))
    } catch {
      consent.value = null
    }
  }

  const saveCookieConsent = (draft: CookieConsentDraft): CookieConsentPreferences => {
    const nextConsent: CookieConsentPreferences = {
      necessary: true,
      analytics: draft.analytics,
      affiliate: draft.affiliate,
      preferences: draft.preferences,
      decidedAt: new Date().toISOString(),
      version: cookieConsentVersion,
    }

    consent.value = nextConsent

    if (import.meta.client) {
      window.localStorage.setItem(cookieConsentStorageKey, JSON.stringify(nextConsent))
    }

    return nextConsent
  }

  const acceptAllCookieConsent = (): CookieConsentPreferences => saveCookieConsent({
    analytics: true,
    affiliate: true,
    preferences: true,
  })

  const rejectOptionalCookieConsent = (): CookieConsentPreferences => saveCookieConsent({
    analytics: false,
    affiliate: false,
    preferences: false,
  })

  return {
    consent,
    hasLoadedCookieConsent,
    hasCookieConsentChoice: computed(() => Boolean(consent.value?.decidedAt)),
    allowsAnalytics: computed(() => Boolean(consent.value?.analytics)),
    allowsAffiliate: computed(() => Boolean(consent.value?.affiliate)),
    allowsPreferences: computed(() => Boolean(consent.value?.preferences)),
    defaultCookieConsent,
    loadCookieConsent,
    saveCookieConsent,
    acceptAllCookieConsent,
    rejectOptionalCookieConsent,
  }
}
