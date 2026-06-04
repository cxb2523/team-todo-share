import { defineStore } from 'pinia'
import axios from 'axios'

export const useTodoStore = defineStore('todo', {
  state: () => ({
    lists: [],
    currentList: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchLists() {
      this.loading = true
      try {
        const response = await axios.get('/todo-lists')
        this.lists = response.data
      } catch (error) {
        this.error = error.response?.data?.message || '获取清单失败'
      } finally {
        this.loading = false
      }
    },

    async fetchList(id) {
      this.loading = true
      try {
        const response = await axios.get(`/todo-lists/${id}`)
        this.currentList = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '获取清单详情失败'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createList(data) {
      try {
        const response = await axios.post('/todo-lists', data)
        this.lists.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '创建清单失败'
        throw error
      }
    },

    async updateList(id, data) {
      try {
        const response = await axios.put(`/todo-lists/${id}`, data)
        const index = this.lists.findIndex(l => l.id === id)
        if (index > -1) {
          this.lists[index] = response.data
        }
        if (this.currentList?.id === id) {
          this.currentList = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '更新清单失败'
        throw error
      }
    },

    async deleteList(id) {
      try {
        await axios.delete(`/todo-lists/${id}`)
        this.lists = this.lists.filter(l => l.id !== id)
        if (this.currentList?.id === id) {
          this.currentList = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || '删除清单失败'
        throw error
      }
    },

    async shareList(listId, data) {
      try {
        const response = await axios.post(`/todo-lists/${listId}/share`, data)
        if (this.currentList?.id === listId) {
          if (!this.currentList.shares) {
            this.currentList.shares = []
          }
          const existingIndex = this.currentList.shares.findIndex(s => s.user_id === response.data.user_id)
          if (existingIndex > -1) {
            this.currentList.shares[existingIndex] = response.data
          } else {
            this.currentList.shares.push(response.data)
          }
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '分享清单失败'
        throw error
      }
    },

    async unshareList(listId, userId) {
      try {
        await axios.delete(`/todo-lists/${listId}/unshare/${userId}`)
        if (this.currentList?.id === listId && this.currentList.shares) {
          this.currentList.shares = this.currentList.shares.filter(s => s.user.id !== userId)
        }
      } catch (error) {
        this.error = error.response?.data?.message || '取消分享失败'
        throw error
      }
    },

    async searchUsers(query) {
      const response = await axios.get(`/todo-lists/search-users?query=${query}`)
      return response.data
    },

    async createTask(listId, data) {
      try {
        const response = await axios.post(`/todo-lists/${listId}/tasks`, data)
        if (this.currentList?.id === listId) {
          if (!this.currentList.tasks) {
            this.currentList.tasks = []
          }
          this.currentList.tasks.push(response.data)
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '创建任务失败'
        throw error
      }
    },

    async updateTask(listId, taskId, data) {
      try {
        const response = await axios.put(`/todo-lists/${listId}/tasks/${taskId}`, data)
        if (this.currentList?.id === listId && this.currentList.tasks) {
          const index = this.currentList.tasks.findIndex(t => t.id === taskId)
          if (index > -1) {
            this.currentList.tasks[index] = response.data
          }
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || '更新任务失败'
        throw error
      }
    },

    async deleteTask(listId, taskId) {
      try {
        await axios.delete(`/todo-lists/${listId}/tasks/${taskId}`)
        if (this.currentList?.id === listId && this.currentList.tasks) {
          this.currentList.tasks = this.currentList.tasks.filter(t => t.id !== taskId)
        }
      } catch (error) {
        this.error = error.response?.data?.message || '删除任务失败'
        throw error
      }
    },

    setupListUpdates(listId) {
      if (typeof window.Echo === 'undefined') return

      window.Echo.private(`todo-list.${listId}`)
        .listen('.task.status.updated', (e) => {
          if (this.currentList?.id === e.list_id && this.currentList.tasks) {
            const task = this.currentList.tasks.find(t => t.id === e.task_id)
            if (task) {
              task.status = e.status
            }
          }
        })
    }
  },

  getters: {
    ownedLists: (state) => {
      const userId = JSON.parse(localStorage.getItem('user') || '{}').id
      return state.lists.filter(l => l.owner_id === userId)
    },
    sharedLists: (state) => {
      const userId = JSON.parse(localStorage.getItem('user') || '{}').id
      return state.lists.filter(l => l.owner_id !== userId)
    }
  }
})
