<script setup lang="ts">
const props = defineProps<{
  siteName: string
  siteSlug: string
}>()

const currentYear = new Date().getFullYear()

const links = computed(() => [
  {
    label: 'Privacybeleid',
    to: `/preview/${props.siteSlug}/privacybeleid`,
  },
  {
    label: 'Cookiebeleid',
    to: `/preview/${props.siteSlug}/cookiebeleid`,
  },
  {
    label: 'Gebruikersvoorwaarden',
    to: `/preview/${props.siteSlug}/gebruikersvoorwaarden`,
  },
])
</script>

<template>
  <footer class="site-footer">
    <p>
      &copy; {{ currentYear }} {{ siteName }}. Alle rechten voorbehouden.
    </p>

    <nav aria-label="Juridische informatie">
      <template v-for="(link, index) in links" :key="link.to">
        <span v-if="index > 0" class="separator" aria-hidden="true">/</span>
        <NuxtLink :to="link.to">
          {{ link.label }}
        </NuxtLink>
      </template>
    </nav>
  </footer>
</template>

<style scoped>
.site-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 14px 24px;
  align-items: center;
  justify-content: space-between;
  padding: 24px clamp(20px, 4vw, 56px);
  border-top: 1px solid rgba(23, 33, 31, 0.1);
  background: #ffffff;
  color: var(--site-text, #17211f);
  font-size: 0.92rem;
}

.site-footer p {
  margin: 0;
  font-weight: 800;
}

.site-footer nav {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
}

.site-footer a,
.separator {
  display: inline-flex;
  align-items: center;
}

.site-footer a {
  color: inherit;
  font-weight: 800;
  text-decoration: none;
}

.separator {
  color: #8b9693;
  font-weight: 800;
}

.site-footer a:hover {
  color: var(--site-primary, #0f766e);
}

@media (max-width: 640px) {
  .site-footer {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
