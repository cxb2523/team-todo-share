import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
  }),

  actions: {
    async login(credentials) {
      const response = await axios.post('/login', credentials)
      this.user = response.data.user
      this.token = response.data.access_token
      localStorage.setItem('user', JSON.stringify(this.user))
      localStorage.setItem('token', this.token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      return response
    },

    async register(credentials) {
      const response = await axios.post('/register', credentials)
      this.user = response.data.user
      this.token = response.data.access_token
      localStorage.setItem('user', JSON.stringify(this.user))
      localStorage.setItem('token', this.token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      return response
    },

    async logout() {
      try {
        await axios.post('/logout')
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
      }
    },

    async fetchUser() {
      const response = await axios.get('/user')
      this.user = response.data
      localStorage.setItem('user', JSON.stringify(this.user))
    }
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    userName: (state) => state.user?.name || '',
    userId: (state) => state.user?.id || null,
  }
})
