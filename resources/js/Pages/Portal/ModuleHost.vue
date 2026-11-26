<script setup>
import { onMounted } from 'vue'

defineProps({
  module: { type: String, required: true },
  newsTypes: { type: Array, default: () => [] },
})

onMounted(() => {
  const root = document.getElementById('portal-inertia')
  let attempts = 0
  const timer = window.setInterval(() => {
    const content = document.querySelector('#app .content')
    attempts += 1

    if (root && content) {
      content.appendChild(root)
      window.clearInterval(timer)
    } else if (attempts >= 300) {
      window.clearInterval(timer)
    }
  }, 100)
})
</script>

<template>
  <div id="portal-module-host" class="portal-module-host"></div>
</template>
