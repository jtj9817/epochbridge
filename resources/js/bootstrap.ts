import axios from 'axios'
import { applyTheme } from '@/lib/theme'

declare global {
  interface Window {
    axios: typeof axios
  }
}

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

applyTheme()

document.addEventListener('inertia:navigate', (event) => {
  const customEvent = event as CustomEvent<{ page?: { url?: string } }>
  const pageUrl = customEvent.detail?.page?.url

  if (typeof pageUrl !== 'string' || pageUrl === '') {
    applyTheme()
    return
  }

  const pathname = new URL(pageUrl, window.location.origin).pathname
  applyTheme(pathname)
})
