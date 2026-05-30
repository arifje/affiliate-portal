<template>
  <main class="site-shell">
    <section>
      <p class="eyebrow">Affiliate Portal</p>
      <h1>Multi-domain storefront frontend</h1>
      <p>
        Nuxt SSR is ready for host-based site resolution, preview URLs, shared
        product pages, and domain-specific themes from the Laravel backend.
      </p>
    </section>
  </main>
</template>

<style scoped>
.site-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 48px 20px;
}

.site-shell section {
  width: min(720px, 100%);
}

.eyebrow {
  margin: 0 0 12px;
  color: #2f6f62;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  font-size: 4rem;
  line-height: 1;
}

p {
  max-width: 620px;
  font-size: 1.1rem;
  line-height: 1.65;
}

@media (max-width: 720px) {
  h1 {
    font-size: 2.3rem;
  }
}
</style>
