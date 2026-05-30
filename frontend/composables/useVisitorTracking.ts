const visitorStorageKey = 'affiliate_portal_visitor_id'

export function getOrCreateVisitorId(): string {
  const visitorId = window.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`

  try {
    const existing = window.localStorage.getItem(visitorStorageKey)

    if (existing) {
      return existing
    }

    window.localStorage.setItem(visitorStorageKey, visitorId)
  } catch {
    return visitorId
  }

  return visitorId
}

function sendSiteVisit(siteSlug: string, apiBase: string): void {
  const endpoint = `${apiBase}/sites/preview/${siteSlug}/visits`
  const body = new URLSearchParams({
    visitor_id: getOrCreateVisitorId(),
    path: window.location.pathname,
  })

  if (document.referrer) {
    body.set('referer', document.referrer)
  }

  if (navigator.sendBeacon?.(endpoint, body)) {
    return
  }

  void $fetch(endpoint, {
    method: 'POST',
    body,
  }).catch(() => {})
}

export function trackSiteVisit(siteSlug: string | null | undefined): void {
  if (import.meta.server || !siteSlug) {
    return
  }

  const { allowsAnalytics } = useCookieConsent()

  if (!allowsAnalytics.value) {
    return
  }

  const config = useRuntimeConfig()

  sendSiteVisit(siteSlug, config.public.apiBase)
}

export function startSiteVisitHeartbeat(siteSlug: string, intervalMs = 60000): () => void {
  if (import.meta.server) {
    return () => {}
  }

  const { allowsAnalytics } = useCookieConsent()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase
  let interval: number | null = null

  const stopInterval = (): void => {
    if (interval === null) {
      return
    }

    window.clearInterval(interval)
    interval = null
  }

  const startInterval = (): void => {
    stopInterval()

    if (!allowsAnalytics.value) {
      return
    }

    sendSiteVisit(siteSlug, apiBase)
    interval = window.setInterval(() => {
      if (allowsAnalytics.value) {
        sendSiteVisit(siteSlug, apiBase)
      }
    }, intervalMs)
  }

  const stopConsentWatcher = watch(allowsAnalytics, startInterval, { immediate: true })

  return () => {
    stopConsentWatcher()
    stopInterval()
  }
}
