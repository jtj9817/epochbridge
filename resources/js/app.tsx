import './bootstrap'

import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot } from 'react-dom/client'

const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx')

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, pages),
  setup({ el, App, props }) {
    createRoot(el!).render(<App {...props} />)
  },
})
