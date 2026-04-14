import { PageProps as InertiaPageProps } from '@inertiajs/core'

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps {
    auth: {
      user: { id: number; name: string; email: string } | null
    }
    ziggy: {
      url: string
      port: number | null
      defaults: Record<string, unknown>
      routes: Record<string, { uri: string; methods: string[] }>
      location: string
    }
    flash?: {
      toast?: { type: 'success' | 'error' | 'info'; message: string }
    }
  }
}
