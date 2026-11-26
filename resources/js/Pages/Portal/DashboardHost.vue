<script setup>
import { onMounted } from 'vue'

defineProps({ mode: { type: String, required: true } })

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
  <div class="portal-dashboard-inertia-host" :data-mode="mode"></div>
</template>
