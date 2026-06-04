<template>
  <div class="dashboard">
    <header class="dashboard-header">
      <div class="header-left">
        <h1>📋 待办事项</h1>
        <span class="user-greeting">欢迎，{{ authStore.user?.name }}</span>
      </div>
      <div class="header-right">
        <button @click="showCreateModal = true" class="btn-create">
          + 新建清单
        </button>
        <button @click="handleLogout" class="btn-logout">退出</button>
      </div>
    </header>

    <main class="dashboard-main">
      <div v-if="todoStore.loading" class="loading">加载中...</div>
      
      <section class="list-section">
        <h2>我的清单</h2>
        <div class="list-grid">
          <div 
            v-for="list in todoStore.ownedLists" 
            :key="list.id"
            class="list-card"
            @click="goToList(list.id)"
          >
            <div class="list-card-header">
              <h3>{{ list.title }}</h3>
              <span class="owner-badge">我创建的</span>
            </div>
            <p v-if="list.description" class="list-description">{{ list.description }}</p>
            <div class="list-meta">
              <span class="task-count">
                {{ list.tasks?.length || 0 }} 个任务
              </span>
              <span v-if="list.shares?.length > 0" class="share-count">
                👥 {{ list.shares.length }} 人共享
              </span>
            </div>
          </div>
          
          <div v-if="todoStore.ownedLists.length === 0" class="empty-state">
            <p>还没有创建任何清单</p>
            <button @click="showCreateModal = true" class="btn-secondary">创建第一个清单</button>
          </div>
        </div>
      </section>

      <section class="list-section" v-if="todoStore.sharedLists.length > 0">
        <h2>与我共享</h2>
        <div class="list-grid">
          <div 
            v-for="list in todoStore.sharedLists" 
            :key="list.id"
            class="list-card shared"
            @click="goToList(list.id)"
          >
            <div class="list-card-header">
              <h3>{{ list.title }}</h3>
              <span class="owner-badge shared">{{ list.owner?.name }}</span>
            </div>
            <p v-if="list.description" class="list-description">{{ list.description }}</p>
            <div class="list-meta">
              <span class="task-count">
                {{ list.tasks?.length || 0 }} 个任务
              </span>
            </div>
          </div>
        </div>
      </section>
    </main>

    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h3>创建新清单</h3>
        <form @submit.prevent="createList">
          <div class="form-group">
            <label>标题</label>
            <input 
              v-model="newList.title" 
              type="text" 
              placeholder="输入清单标题"
              required
              ref="titleInput"
            />
          </div>
          <div class="form-group">
            <label>描述（可选）</label>
            <textarea 
              v-model="newList.description" 
              placeholder="输入清单描述"
              rows="3"
            ></textarea>
          </div>
          <div class="form-actions">
            <button type="button" @click="showCreateModal = false" class="btn-cancel">取消</button>
            <button type="submit" class="btn-primary">创建</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTodoStore } from '../stores/todo'

const router = useRouter()
const authStore = useAuthStore()
const todoStore = useTodoStore()

const showCreateModal = ref(false)
const titleInput = ref(null)
const newList = ref({
  title: '',
  description: ''
})

onMounted(() => {
  todoStore.fetchLists()
})

function goToList(id) {
  router.push(`/lists/${id}`)
}

async function createList() {
  try {
    await todoStore.createList(newList.value)
    showCreateModal.value = false
    newList.value = { title: '', description: '' }
  } catch (e) {
    console.error('创建清单失败:', e)
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f5f7fa;
}

.dashboard-header {
  background: white;
  padding: 20px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.header-left h1 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.user-greeting {
  color: #666;
  font-size: 14px;
  margin-left: 12px;
}

.header-right {
  display: flex;
  gap: 12px;
}

.btn-create {
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-create:hover {
  transform: translateY(-2px);
}

.btn-logout {
  padding: 10px 20px;
  background: #f0f0f0;
  color: #666;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-logout:hover {
  background: #e0e0e0;
}

.dashboard-main {
  padding: 40px;
  max-width: 1400px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.list-section {
  margin-bottom: 40px;
}

.list-section h2 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 20px;
}

.list-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.list-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #667eea;
}

.list-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.list-card.shared {
  border-left-color: #48bb78;
}

.list-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.list-card-header h3 {
  margin: 0;
  font-size: 18px;
  color: #333;
  flex: 1;
}

.owner-badge {
  font-size: 12px;
  padding: 4px 10px;
  background: #e3e8ff;
  color: #667eea;
  border-radius: 20px;
  margin-left: 12px;
}

.owner-badge.shared {
  background: #c6f6d5;
  color: #2f855a;
}

.list-description {
  color: #666;
  font-size: 14px;
  margin: 0 0 16px 0;
  line-height: 1.5;
}

.list-meta {
  display: flex;
  gap: 16px;
  color: #999;
  font-size: 13px;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
}

.empty-state p {
  color: #999;
  margin-bottom: 20px;
}

.btn-secondary {
  padding: 10px 24px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 16px;
  padding: 32px;
  width: 100%;
  max-width: 480px;
}

.modal h3 {
  margin: 0 0 24px 0;
  color: #333;
  font-size: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #333;
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #667eea;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.btn-cancel {
  padding: 12px 24px;
  background: #f0f0f0;
  color: #666;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
</style>
