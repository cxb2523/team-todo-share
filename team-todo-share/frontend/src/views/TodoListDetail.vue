<template>
  <div class="list-detail">
    <header class="detail-header">
      <div class="header-left">
        <button @click="$router.push('/')" class="btn-back">← 返回</button>
        <div class="list-info">
          <h1>{{ todoStore.currentList?.title }}</h1>
          <p class="list-owner">
            创建者: {{ todoStore.currentList?.owner?.name }}
            <span v-if="isOwner" class="owner-badge">我</span>
          </p>
        </div>
      </div>
      <div class="header-right">
        <button v-if="canEdit" @click="showShareModal = true" class="btn-share">
          👥 共享
        </button>
        <button v-if="canEdit" @click="showTaskModal = true" class="btn-create">
          + 添加任务
        </button>
      </div>
    </header>

    <main class="detail-main">
      <div v-if="todoStore.loading" class="loading">加载中...</div>
      
      <div v-if="todoStore.currentList" class="tasks-container">
        <div v-if="tasksByStatus.pending.length > 0" class="status-section">
          <h2><span class="status-dot pending"></span> 待处理 ({{ tasksByStatus.pending.length }})</h2>
          <div class="task-list">
            <div v-for="task in tasksByStatus.pending" :key="task.id" class="task-card">
              <div class="task-content">
                <h4>{{ task.title }}</h4>
                <p v-if="task.description" class="task-desc">{{ task.description }}</p>
                <div class="task-meta">
                  <span v-if="task.assignedUser" class="assignee">
                    👤 {{ task.assignedUser.name }}
                  </span>
                  <span v-if="task.due_date" class="due-date">
                    📅 {{ formatDate(task.due_date) }}
                  </span>
                  <span class="priority" :class="`priority-${task.priority}`">
                    优先级 {{ task.priority }}
                  </span>
                </div>
              </div>
              <div v-if="canEdit" class="task-actions">
                <select @change="updateTaskStatus(task, $event.target.value)" :value="task.status" class="status-select">
                  <option value="pending">待处理</option>
                  <option value="in_progress">进行中</option>
                  <option value="completed">已完成</option>
                  <option value="cancelled">已取消</option>
                </select>
                <button @click="editTask(task)" class="btn-icon">✏️</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="tasksByStatus.in_progress.length > 0" class="status-section">
          <h2><span class="status-dot in_progress"></span> 进行中 ({{ tasksByStatus.in_progress.length }})</h2>
          <div class="task-list">
            <div v-for="task in tasksByStatus.in_progress" :key="task.id" class="task-card in_progress">
              <div class="task-content">
                <h4>{{ task.title }}</h4>
                <p v-if="task.description" class="task-desc">{{ task.description }}</p>
                <div class="task-meta">
                  <span v-if="task.assignedUser" class="assignee">
                    👤 {{ task.assignedUser.name }}
                  </span>
                  <span v-if="task.due_date" class="due-date">
                    📅 {{ formatDate(task.due_date) }}
                  </span>
                  <span class="priority" :class="`priority-${task.priority}`">
                    优先级 {{ task.priority }}
                  </span>
                </div>
              </div>
              <div v-if="canEdit" class="task-actions">
                <select @change="updateTaskStatus(task, $event.target.value)" :value="task.status" class="status-select">
                  <option value="pending">待处理</option>
                  <option value="in_progress">进行中</option>
                  <option value="completed">已完成</option>
                  <option value="cancelled">已取消</option>
                </select>
                <button @click="editTask(task)" class="btn-icon">✏️</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="tasksByStatus.completed.length > 0" class="status-section">
          <h2><span class="status-dot completed"></span> 已完成 ({{ tasksByStatus.completed.length }})</h2>
          <div class="task-list">
            <div v-for="task in tasksByStatus.completed" :key="task.id" class="task-card completed">
              <div class="task-content">
                <h4><s>{{ task.title }}</s></h4>
                <p v-if="task.description" class="task-desc"><s>{{ task.description }}</s></p>
                <div class="task-meta">
                  <span v-if="task.assignedUser" class="assignee">
                    👤 {{ task.assignedUser.name }}
                  </span>
                  <span class="priority" :class="`priority-${task.priority}`">
                    优先级 {{ task.priority }}
                  </span>
                </div>
              </div>
              <div v-if="canEdit" class="task-actions">
                <select @change="updateTaskStatus(task, $event.target.value)" :value="task.status" class="status-select">
                  <option value="pending">待处理</option>
                  <option value="in_progress">进行中</option>
                  <option value="completed">已完成</option>
                  <option value="cancelled">已取消</option>
                </select>
                <button @click="editTask(task)" class="btn-icon">✏️</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="totalTasks === 0" class="empty-state">
          <p>还没有任何任务</p>
          <button v-if="canEdit" @click="showTaskModal = true" class="btn-secondary">创建第一个任务</button>
        </div>
      </div>
    </main>

    <div v-if="showTaskModal" class="modal-overlay" @click.self="closeTaskModal">
      <div class="modal">
        <h3>{{ editingTask ? '编辑任务' : '创建新任务' }}</h3>
        <form @submit.prevent="saveTask">
          <div class="form-group">
            <label>标题</label>
            <input v-model="taskForm.title" type="text" placeholder="输入任务标题" required />
          </div>
          <div class="form-group">
            <label>描述（可选）</label>
            <textarea v-model="taskForm.description" placeholder="输入任务描述" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label>状态</label>
              <select v-model="taskForm.status">
                <option value="pending">待处理</option>
                <option value="in_progress">进行中</option>
                <option value="completed">已完成</option>
                <option value="cancelled">已取消</option>
              </select>
            </div>
            <div class="form-group half">
              <label>优先级</label>
              <select v-model.number="taskForm.priority">
                <option :value="1">低</option>
                <option :value="2">中</option>
                <option :value="3">高</option>
                <option :value="4">很高</option>
                <option :value="5">最高</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>分配给（可选）</label>
            <input 
              v-model="shareSearchQuery" 
              type="text" 
              placeholder="搜索用户..."
              @input="searchShareUsers"
            />
            <div v-if="shareSearchResults.length > 0" class="search-results">
              <div 
                v-for="user in shareSearchResults" 
                :key="user.id"
                class="search-item"
                @click="taskForm.assigned_to = user.id; taskForm.assignedUser = user; shareSearchResults = []; shareSearchQuery = ''"
              >
                {{ user.name }} ({{ user.email }})
              </div>
            </div>
            <div v-if="taskForm.assignedUser" class="selected-user">
              已选择: {{ taskForm.assignedUser.name }}
              <button type="button" @click="taskForm.assigned_to = null; taskForm.assignedUser = null" class="remove-btn">×</button>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" @click="closeTaskModal" class="btn-cancel">取消</button>
            <button type="submit" class="btn-primary">{{ editingTask ? '保存' : '创建' }}</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showShareModal" class="modal-overlay" @click.self="showShareModal = false">
      <div class="modal large">
        <h3>共享清单</h3>
        
        <div class="share-section">
          <h4>添加协作者</h4>
          <div class="share-search">
            <input 
              v-model="shareSearchQuery" 
              type="text" 
              placeholder="搜索用户邮箱或姓名..."
              @input="searchShareUsers"
            />
            <select v-model="newSharePermission" class="permission-select">
              <option value="view">查看</option>
              <option value="edit">编辑</option>
              <option value="admin">管理</option>
            </select>
          </div>
          <div v-if="shareSearchResults.length > 0" class="search-results">
            <div 
              v-for="user in shareSearchResults" 
              :key="user.id"
              class="search-item"
              @click="shareList(user)"
            >
              {{ user.name }} ({{ user.email }})
            </div>
          </div>
        </div>

        <div class="share-section" v-if="todoStore.currentList?.shares?.length > 0">
          <h4>当前协作者</h4>
          <div class="share-list">
            <div v-for="share in todoStore.currentList.shares" :key="share.id" class="share-item">
              <div class="share-user">
                <span class="user-name">{{ share.user.name }}</span>
                <span class="user-email">{{ share.user.email }}</span>
              </div>
              <div class="share-permission">
                <select 
                  :value="share.permission" 
                  @change="updateSharePermission(share, $event.target.value)"
                  :disabled="share.user_id === authStore.userId"
                >
                  <option value="view">查看</option>
                  <option value="edit">编辑</option>
                  <option value="admin">管理</option>
                </select>
              </div>
              <button 
                v-if="share.user_id !== authStore.userId" 
                @click="removeShare(share.user_id)" 
                class="btn-remove"
              >
                移除
              </button>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button @click="showShareModal = false" class="btn-primary">完成</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTodoStore } from '../stores/todo'

const route = useRoute()
const authStore = useAuthStore()
const todoStore = useTodoStore()

const showTaskModal = ref(false)
const showShareModal = ref(false)
const editingTask = ref(null)
const shareSearchQuery = ref('')
const shareSearchResults = ref([])
const newSharePermission = ref('view')

const taskForm = ref({
  title: '',
  description: '',
  status: 'pending',
  priority: 1,
  assigned_to: null,
  assignedUser: null
})

const isOwner = computed(() => {
  return todoStore.currentList?.owner_id === authStore.userId
})

const canEdit = computed(() => {
  if (!todoStore.currentList) return false
  if (isOwner.value) return true
  const share = todoStore.currentList.shares?.find(s => s.user_id === authStore.userId)
  return share && (share.permission === 'edit' || share.permission === 'admin')
})

const tasksByStatus = computed(() => {
  const tasks = todoStore.currentList?.tasks || []
  return {
    pending: tasks.filter(t => t.status === 'pending'),
    in_progress: tasks.filter(t => t.status === 'in_progress'),
    completed: tasks.filter(t => t.status === 'completed'),
    cancelled: tasks.filter(t => t.status === 'cancelled')
  }
})

const totalTasks = computed(() => {
  return todoStore.currentList?.tasks?.length || 0
})

onMounted(async () => {
  await todoStore.fetchList(route.params.id)
  todoStore.setupListUpdates(route.params.id)
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('zh-CN')
}

function editTask(task) {
  editingTask.value = task
  taskForm.value = {
    title: task.title,
    description: task.description || '',
    status: task.status,
    priority: task.priority,
    assigned_to: task.assigned_to,
    assignedUser: task.assignedUser || null
  }
  showTaskModal.value = true
}

async function saveTask() {
  try {
    if (editingTask.value) {
      await todoStore.updateTask(route.params.id, editingTask.value.id, taskForm.value)
    } else {
      await todoStore.createTask(route.params.id, taskForm.value)
    }
    closeTaskModal()
  } catch (e) {
    console.error('保存任务失败:', e)
  }
}

function closeTaskModal() {
  showTaskModal.value = false
  editingTask.value = null
  taskForm.value = {
    title: '',
    description: '',
    status: 'pending',
    priority: 1,
    assigned_to: null,
    assignedUser: null
  }
}

async function updateTaskStatus(task, status) {
  try {
    await todoStore.updateTask(route.params.id, task.id, { status })
  } catch (e) {
    console.error('更新状态失败:', e)
  }
}

async function searchShareUsers() {
  if (shareSearchQuery.value.length < 2) {
    shareSearchResults.value = []
    return
  }
  try {
    shareSearchResults.value = await todoStore.searchUsers(shareSearchQuery.value)
  } catch (e) {
    console.error('搜索用户失败:', e)
  }
}

async function shareList(user) {
  try {
    await todoStore.shareList(route.params.id, {
      email: user.email,
      permission: newSharePermission.value
    })
    shareSearchQuery.value = ''
    shareSearchResults.value = []
  } catch (e) {
    console.error('共享失败:', e)
  }
}

async function updateSharePermission(share, permission) {
  try {
    await todoStore.shareList(route.params.id, {
      email: share.user.email,
      permission: permission
    })
  } catch (e) {
    console.error('更新权限失败:', e)
  }
}

async function removeShare(userId) {
  try {
    await todoStore.unshareList(route.params.id, userId)
  } catch (e) {
    console.error('移除共享失败:', e)
  }
}
</script>

<style scoped>
.list-detail {
  min-height: 100vh;
  background: #f5f7fa;
}

.detail-header {
  background: white;
  padding: 20px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.btn-back {
  padding: 8px 16px;
  background: #f0f0f0;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #666;
}

.btn-back:hover {
  background: #e0e0e0;
}

.list-info h1 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.list-owner {
  margin: 4px 0 0 0;
  color: #666;
  font-size: 14px;
}

.owner-badge {
  margin-left: 8px;
  padding: 2px 8px;
  background: #e3e8ff;
  color: #667eea;
  border-radius: 10px;
  font-size: 12px;
}

.header-right {
  display: flex;
  gap: 12px;
}

.btn-share {
  padding: 10px 20px;
  background: #48bb78;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-share:hover {
  background: #38a169;
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

.detail-main {
  padding: 40px;
  max-width: 1200px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}

.status-section {
  margin-bottom: 32px;
}

.status-section h2 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.status-dot.pending { background: #f6ad55; }
.status-dot.in_progress { background: #667eea; }
.status-dot.completed { background: #48bb78; }

.task-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.task-card {
  background: white;
  border-radius: 10px;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #f6ad55;
}

.task-card.in_progress {
  border-left-color: #667eea;
}

.task-card.completed {
  border-left-color: #48bb78;
  opacity: 0.8;
}

.task-content h4 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 16px;
}

.task-desc {
  margin: 0 0 12px 0;
  color: #666;
  font-size: 14px;
  line-height: 1.5;
}

.task-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.task-meta span {
  font-size: 13px;
  color: #999;
}

.priority.priority-1 { color: #999; }
.priority.priority-2 { color: #f6ad55; }
.priority.priority-3 { color: #ed8936; }
.priority.priority-4 { color: #e53e3e; }
.priority.priority-5 { color: #c53030; font-weight: 600; }

.task-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.status-select {
  padding: 6px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 13px;
  background: white;
  cursor: pointer;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: #f0f0f0;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-icon:hover {
  background: #e0e0e0;
}

.empty-state {
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
  max-width: 560px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal.large {
  max-width: 640px;
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
.form-group select,
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
.form-group select:focus,
.form-group textarea:focus {
  border-color: #667eea;
}

.form-row {
  display: flex;
  gap: 16px;
}

.form-group.half {
  flex: 1;
}

.selected-user {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: #e3e8ff;
  border-radius: 8px;
  margin-top: 8px;
  font-size: 14px;
  color: #667eea;
}

.remove-btn {
  background: none;
  border: none;
  color: #667eea;
  font-size: 20px;
  cursor: pointer;
  padding: 0 4px;
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

.share-section {
  margin-bottom: 24px;
}

.share-section h4 {
  margin: 0 0 12px 0;
  color: #333;
  font-size: 16px;
}

.share-search {
  display: flex;
  gap: 12px;
}

.permission-select {
  max-width: 120px;
}

.search-results {
  margin-top: 8px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.search-item {
  padding: 10px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
}

.search-item:hover {
  background: #f8f9fa;
}

.share-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.share-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-radius: 8px;
  gap: 12px;
}

.share-user {
  flex: 1;
}

.user-name {
  display: block;
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.user-email {
  display: block;
  font-size: 12px;
  color: #999;
}

.share-permission select {
  padding: 6px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 13px;
  background: white;
}

.btn-remove {
  padding: 6px 12px;
  background: #fef2f2;
  color: #e53e3e;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
}

.btn-remove:hover {
  background: #fee2e2;
}
</style>
