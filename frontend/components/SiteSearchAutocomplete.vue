<script setup lang="ts">
type SearchSuggestionType = 'product' | 'category' | 'brand'

type SearchSuggestion = {
  type: SearchSuggestionType
  title: string
  subtitle: string | null
  slug: string
  image_url: string | null
  price: string | number | null
  currency: string | null
  products_count: number | null
}

type SearchSuggestionResponse = {
  query: string
  suggestions: SearchSuggestion[]
}

const props = withDefaults(defineProps<{
  siteSlug: string
  inputId: string
  placeholder: string
  initialQuery?: string
  contrast?: boolean
}>(), {
  initialQuery: '',
  contrast: false,
})

const config = useRuntimeConfig()
const query = ref(props.initialQuery)
const suggestions = ref<SearchSuggestion[]>([])
const isFocused = ref(false)
const isLoading = ref(false)
const activeIndex = ref(-1)
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let closeTimer: ReturnType<typeof setTimeout> | null = null
let activeController: AbortController | null = null

const trimmedQuery = computed(() => query.value.trim())
const canSearch = computed(() => trimmedQuery.value.length >= 2 && Boolean(props.siteSlug))
const showSuggestions = computed(() => isFocused.value && canSearch.value)
const hasSuggestions = computed(() => suggestions.value.length > 0)
const listboxId = computed(() => `${props.inputId}-suggestions`)
const activeDescendant = computed(() => activeIndex.value >= 0 ? `${listboxId.value}-${activeIndex.value}` : undefined)

watch(() => props.initialQuery, (value) => {
  if (value !== query.value) {
    query.value = value || ''
  }
})

watch(trimmedQuery, (value) => {
  activeIndex.value = -1

  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }

  if (!canSearch.value) {
    abortActiveRequest()
    suggestions.value = []
    isLoading.value = false

    return
  }

  debounceTimer = setTimeout(() => {
    fetchSuggestions(value)
  }, 180)
})

async function fetchSuggestions(search: string): Promise<void> {
  abortActiveRequest()

  const controller = new AbortController()
  activeController = controller
  isLoading.value = true

  try {
    const response = await $fetch<SearchSuggestionResponse>(
      `${config.public.apiBase}/sites/preview/${props.siteSlug}/search-suggestions`,
      {
        query: { q: search },
        signal: controller.signal,
      },
    )

    if (!controller.signal.aborted) {
      suggestions.value = response.suggestions
    }
  } catch (error) {
    if (!controller.signal.aborted) {
      suggestions.value = []
    }
  } finally {
    if (activeController === controller) {
      isLoading.value = false
      activeController = null
    }
  }
}

function abortActiveRequest(): void {
  activeController?.abort()
  activeController = null
}

function submitSearch(): void {
  if (!trimmedQuery.value) {
    return
  }

  navigateTo({
    path: `/preview/${props.siteSlug}/search`,
    query: { q: trimmedQuery.value },
  })
}

function suggestionPath(suggestion: SearchSuggestion): string {
  if (suggestion.type === 'product') {
    return `/preview/${props.siteSlug}/products/${suggestion.slug}`
  }

  if (suggestion.type === 'category') {
    return `/preview/${props.siteSlug}/categories/${suggestion.slug}`
  }

  return `/preview/${props.siteSlug}/brands/${suggestion.slug}`
}

function openSuggestion(suggestion: SearchSuggestion): void {
  query.value = suggestion.title
  navigateTo(suggestionPath(suggestion))
}

function moveActive(delta: number): void {
  if (!hasSuggestions.value) {
    return
  }

  const nextIndex = activeIndex.value + delta
  const lastIndex = suggestions.value.length - 1

  if (nextIndex < 0) {
    activeIndex.value = lastIndex
  } else if (nextIndex > lastIndex) {
    activeIndex.value = 0
  } else {
    activeIndex.value = nextIndex
  }
}

function handleEnter(): void {
  const activeSuggestion = suggestions.value[activeIndex.value]

  if (activeSuggestion) {
    openSuggestion(activeSuggestion)

    return
  }

  submitSearch()
}

function handleFocus(): void {
  if (closeTimer) {
    clearTimeout(closeTimer)
  }

  isFocused.value = true
}

function handleBlur(): void {
  closeTimer = setTimeout(() => {
    isFocused.value = false
    activeIndex.value = -1
  }, 120)
}

function typeLabel(type: SearchSuggestionType): string {
  if (type === 'product') {
    return 'Product'
  }

  if (type === 'category') {
    return 'Categorie'
  }

  return 'Merk'
}

function typeIcon(type: SearchSuggestionType): string {
  if (type === 'product') {
    return 'i-lucide-shopping-bag'
  }

  if (type === 'category') {
    return 'i-lucide-layout-grid'
  }

  return 'i-lucide-badge-check'
}

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }

  if (closeTimer) {
    clearTimeout(closeTimer)
  }

  abortActiveRequest()
})
</script>

<template>
  <form
    class="search-panel"
    :class="{ 'is-contrast': contrast }"
    role="search"
    @submit.prevent="submitSearch"
  >
    <label :for="inputId">Search</label>

    <div class="search-shell">
      <div class="search-box">
        <UIcon name="i-lucide-search" class="search-icon" aria-hidden="true" />
        <input
          :id="inputId"
          v-model="query"
          type="search"
          :placeholder="placeholder"
          autocomplete="off"
          role="combobox"
          :aria-expanded="showSuggestions"
          :aria-controls="listboxId"
          :aria-activedescendant="activeDescendant"
          @click="handleFocus"
          @focus="handleFocus"
          @input="handleFocus"
          @blur="handleBlur"
          @keydown.down.prevent="moveActive(1)"
          @keydown.up.prevent="moveActive(-1)"
          @keydown.enter.prevent="handleEnter"
          @keydown.esc="isFocused = false"
        >
        <button type="submit">
          <span>Zoeken</span>
          <UIcon name="i-lucide-arrow-right" aria-hidden="true" />
        </button>
      </div>

      <div
        v-if="showSuggestions"
        :id="listboxId"
        class="suggestion-menu"
        role="listbox"
      >
        <button
          class="suggestion-row search-action"
          type="button"
          @mousedown.prevent="submitSearch"
        >
          <span class="suggestion-icon">
            <UIcon name="i-lucide-search" aria-hidden="true" />
          </span>
          <span>
            <strong>Zoek naar "{{ trimmedQuery }}"</strong>
            <small>Bekijk alle zoekresultaten</small>
          </span>
          <UIcon name="i-lucide-arrow-up-right" class="suggestion-arrow" aria-hidden="true" />
        </button>

        <div v-if="isLoading" class="suggestion-status">
          <UIcon name="i-lucide-loader-circle" aria-hidden="true" />
          <span>Suggesties laden...</span>
        </div>

        <template v-else-if="hasSuggestions">
          <button
            v-for="(suggestion, index) in suggestions"
            :id="`${listboxId}-${index}`"
            :key="`${suggestion.type}-${suggestion.slug}`"
            type="button"
            class="suggestion-row"
            :class="{ 'is-active': activeIndex === index }"
            role="option"
            :aria-selected="activeIndex === index"
            @mouseenter="activeIndex = index"
            @mousedown.prevent="openSuggestion(suggestion)"
          >
            <span class="suggestion-thumb">
              <img
                v-if="suggestion.type === 'product' && suggestion.image_url"
                :src="suggestion.image_url"
                :alt="suggestion.title"
              >
              <UIcon v-else :name="typeIcon(suggestion.type)" aria-hidden="true" />
            </span>
            <span class="suggestion-copy">
              <small>{{ typeLabel(suggestion.type) }}</small>
              <strong>{{ suggestion.title }}</strong>
              <em v-if="suggestion.subtitle">{{ suggestion.subtitle }}</em>
            </span>
            <UIcon name="i-lucide-arrow-up-right" class="suggestion-arrow" aria-hidden="true" />
          </button>
        </template>

        <div v-else class="suggestion-status">
          <UIcon name="i-lucide-search-x" aria-hidden="true" />
          <span>Geen suggesties gevonden</span>
        </div>
      </div>
    </div>
  </form>
</template>

<style scoped>
.search-panel {
  position: relative;
  width: min(720px, 100%);
}

.search-panel label {
  display: block;
  margin-bottom: 9px;
  color: var(--site-text);
  font-size: 0.82rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.search-panel.is-contrast label {
  color: rgba(255, 255, 255, 0.86);
}

.search-shell {
  position: relative;
}

.search-box {
  min-height: 64px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 0 10px 0 18px;
  border: 1px solid #cfd9d5;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(23, 33, 31, 0.08);
}

.search-icon {
  width: 20px;
  height: 20px;
  color: var(--site-primary);
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  color: var(--site-text);
  font: inherit;
  font-size: 1.05rem;
}

.search-box button {
  min-height: 44px;
  display: inline-flex;
  gap: 8px;
  align-items: center;
  justify-content: center;
  padding: 0 16px;
  border: 0;
  border-radius: 8px;
  background: var(--site-primary);
  color: #ffffff;
  cursor: pointer;
  font: inherit;
  font-weight: 900;
}

.search-box button:hover {
  background: var(--site-primary-dark);
}

.suggestion-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  left: 0;
  z-index: 30;
  max-height: min(430px, 70vh);
  overflow: auto;
  padding: 8px;
  border: 1px solid rgba(23, 33, 31, 0.12);
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 26px 70px rgba(23, 33, 31, 0.18);
  color: var(--site-text);
}

.suggestion-row {
  width: 100%;
  display: grid;
  grid-template-columns: 48px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
  min-height: 60px;
  padding: 8px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font: inherit;
  text-align: left;
}

.suggestion-row:hover,
.suggestion-row.is-active {
  background: var(--site-soft);
}

.search-action {
  border-bottom: 1px solid #edf1ef;
  border-radius: 8px 8px 0 0;
}

.suggestion-icon,
.suggestion-thumb {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  overflow: hidden;
  border-radius: 8px;
  background: var(--site-muted);
  color: var(--site-primary);
}

.suggestion-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #ffffff;
}

.suggestion-copy,
.search-action span:nth-child(2) {
  display: grid;
  gap: 2px;
  min-width: 0;
}

.suggestion-copy small {
  color: var(--site-primary);
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.suggestion-copy strong,
.search-action strong {
  overflow: hidden;
  font-size: 0.94rem;
  font-weight: 900;
  line-height: 1.25;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.suggestion-copy em,
.search-action small {
  overflow: hidden;
  color: #687672;
  font-size: 0.82rem;
  font-style: normal;
  line-height: 1.35;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.suggestion-arrow {
  color: #8a9692;
}

.suggestion-status {
  display: flex;
  gap: 9px;
  align-items: center;
  padding: 16px 12px 10px;
  color: #687672;
  font-size: 0.9rem;
  font-weight: 800;
}

@media (max-width: 640px) {
  .search-box {
    grid-template-columns: auto 1fr;
    min-height: 56px;
    padding: 0 14px 14px;
  }

  .search-icon,
  .search-box input {
    margin-top: 14px;
  }

  .search-box button {
    grid-column: 1 / -1;
  }

  .suggestion-row {
    grid-template-columns: 42px minmax(0, 1fr);
  }

  .suggestion-icon,
  .suggestion-thumb {
    width: 42px;
    height: 42px;
  }

  .suggestion-arrow {
    display: none;
  }
}
</style>
