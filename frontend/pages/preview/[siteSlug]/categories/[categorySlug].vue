<script setup lang="ts">
const route = useRoute()
</script>

<template>
  <PreviewProductListing
    :site-slug="String(route.params.siteSlug)"
    :category-slug="String(route.params.categorySlug)"
    mode="category"
  />
</template>
