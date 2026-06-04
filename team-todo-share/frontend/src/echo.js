import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const token = localStorage.getItem('token')

if (token) {
  window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'todo-app-key',
    cluster: 'mt1',
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
    authEndpoint: '/broadcasting/auth',
  })
}
