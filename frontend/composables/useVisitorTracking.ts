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

export function trackSiteVisit(siteSlug: string | null | undefined): void {
  if (import.meta.server || !siteSlug) {
    return
  }

  const config = useRuntimeConfig()
  const endpoint = `${config.public.apiBase}/sites/preview/${siteSlug}/visits`
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

export function startSiteVisitHeartbeat(siteSlug: string, intervalMs = 60000): () => void {
  if (import.meta.server) {
    return () => {}
  }

  trackSiteVisit(siteSlug)

  const interval = window.setInterval(() => {
    trackSiteVisit(siteSlug)
  }, intervalMs)

  return () => window.clearInterval(interval)
}
