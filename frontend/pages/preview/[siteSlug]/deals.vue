<script setup lang="ts">
const route = useRoute()
</script>

<template>
  <PreviewProductListing
    :site-slug="String(route.params.siteSlug)"
    mode="deals"
  />
</template>
