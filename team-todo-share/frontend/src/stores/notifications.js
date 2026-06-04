import { defineStore } from 'pinia'
import axios from 'axios'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
  }),

  actions: {
    async fetchUnread() {
      const response = await axios.get('/notifications/unread')
      this.notifications = response.data.notifications
      this.unreadCount = response.data.count
    },

    async markAsRead(id) {
      await axios.put(`/notifications/${id}/read`)
      const index = this.notifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.notifications[index].read = true
        this.unreadCount--
      }
    },

    async markAllAsRead() {
      await axios.put('/notifications/mark-all-read')
      this.notifications.forEach(n => n.read = true)
      this.unreadCount = 0
    },

    addNotification(notification) {
      this.notifications.unshift(notification)
      this.unreadCount++
    },

    setupRealTimeListeners(userId) {
      if (typeof window.Echo === 'undefined') return

      window.Echo.private(`user.${userId}`)
        .listen('.list.shared', (e) => {
          this.addNotification({
            id: Date.now(),
            type: 'list_shared',
            message: e.message,
            data: e,
            read: false,
            created_at: new Date().toISOString()
          })
        })
        .listen('.task.status.updated', (e) => {
          this.addNotification({
            id: Date.now(),
            type: 'task_status_updated',
            message: e.message,
            data: e,
            read: false,
            created_at: new Date().toISOString()
          })
        })
    }
  }
})
