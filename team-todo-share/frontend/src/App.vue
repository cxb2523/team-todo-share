<template>
  <div id="app">
    <NotificationToast />
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useNotificationStore } from './stores/notifications'
import NotificationToast from './components/NotificationToast.vue'

const authStore = useAuthStore()
const notificationStore = useNotificationStore()

onMounted(() => {
  if (authStore.isAuthenticated) {
    notificationStore.fetchUnread()
    notificationStore.setupRealTimeListeners(authStore.userId)
  }
})
</script>

<style>
#app {
  min-height: 100vh;
}
</style>
