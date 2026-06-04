<template>
  <div v-if="notificationStore.unreadCount > 0" class="notification-toast">
    <div class="toast-header">
      <span>新通知 ({{ notificationStore.unreadCount }})</span>
      <button @click="markAllRead" class="mark-all-btn">全部已读</button>
    </div>
    <div class="toast-list">
      <div 
        v-for="notification in notificationStore.notifications.filter(n => !n.read).slice(0, 5)" 
        :key="notification.id"
        class="toast-item"
        @click="markAsRead(notification.id)"
      >
        <div class="toast-icon">{{ getIcon(notification.type) }}</div>
        <div class="toast-content">
          <p>{{ notification.message }}</p>
          <small>{{ formatDate(notification.created_at) }}</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useNotificationStore } from '../stores/notifications'

const notificationStore = useNotificationStore()

function getIcon(type) {
  switch (type) {
    case 'list_shared':
      return '📤'
    case 'task_status_updated':
      return '✅'
    case 'task_assigned':
      return '📋'
    default:
      return '🔔'
  }
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  
  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)} 分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} 小时前`
  return date.toLocaleDateString('zh-CN')
}

function markAsRead(id) {
  notificationStore.markAsRead(id)
}

function markAllRead() {
  notificationStore.markAllAsRead()
}
</script>

<style scoped>
.notification-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 350px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
}

.toast-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}

.mark-all-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 12px;
  transition: background 0.2s;
}

.mark-all-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.toast-list {
  max-height: 300px;
  overflow-y: auto;
}

.toast-item {
  display: flex;
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.2s;
}

.toast-item:hover {
  background: #f8f9fa;
}

.toast-icon {
  font-size: 24px;
  margin-right: 12px;
}

.toast-content p {
  margin: 0;
  font-size: 14px;
  color: #333;
  line-height: 1.4;
}

.toast-content small {
  color: #999;
  font-size: 12px;
}
</style>
