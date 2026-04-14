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

// Vite import.meta extensions
interface ImportMetaEnv {
  readonly VITE_APP_NAME: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
  glob<T = unknown>(
    pattern: string,
    options?: { eager?: boolean }
  ): Record<string, () => Promise<T>>
}
