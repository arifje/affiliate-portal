<script setup lang="ts">
const route = useRoute()
</script>

<template>
  <PreviewProductListing
    :site-slug="String(route.params.siteSlug)"
    :brand-slug="String(route.params.brandSlug)"
    mode="brand"
  />
</template>
