<script setup>
// Small inline SVG icons (Feather style, same look as the Phoenix theme icons).
// Pure SVG on purpose: no external icon script has to rewrite the DOM that Vue owns.
defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 16 },
})
</script>

<template>
  <svg class="portal-icon" :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <template v-if="name === 'edit'"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" /></template>
    <template v-else-if="name === 'trash'"><polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" /></template>
    <template v-else-if="name === 'plus'"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></template>
    <template v-else-if="name === 'search'"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></template>
    <template v-else-if="name === 'arrow-left'"><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></template>
    <template v-else-if="name === 'file'"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /></template>
  </svg>
</template>

<style scoped>
.portal-icon { flex: none; vertical-align: -0.125em; }
</style>
