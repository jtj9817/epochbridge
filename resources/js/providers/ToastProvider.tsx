import {
  createContext,
  useContext,
  useState,
  useCallback,
  type ReactNode,
} from 'react'

type ToastType = 'success' | 'error' | 'info'

interface Toast {
  id: string
  type: ToastType
  message: string
}

interface ToastContextValue {
  toasts: Toast[]
  addToast: (type: ToastType, message: string) => void
  removeToast: (id: string) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

const TOAST_DURATION_MS = 4000

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const removeToast = useCallback((id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
  }, [])

  const addToast = useCallback(
    (type: ToastType, message: string) => {
      const id = crypto.randomUUID()
      setToasts((prev) => [...prev, { id, type, message }])
      setTimeout(() => removeToast(id), TOAST_DURATION_MS)
    },
    [removeToast]
  )

  return (
    <ToastContext.Provider value={{ toasts, addToast, removeToast }}>
      {children}
      <ToastContainer toasts={toasts} onDismiss={removeToast} />
    </ToastContext.Provider>
  )
}

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext)
  if (!ctx) {
    throw new Error('useToast must be used within a ToastProvider')
  }
  return ctx
}

function ToastContainer({
  toasts,
  onDismiss,
}: {
  toasts: Toast[]
  onDismiss: (id: string) => void
}) {
  if (toasts.length === 0) return null

  return (
    <div className="fixed bottom-4 right-4 z-[100] flex flex-col gap-3 max-w-sm">
      {toasts.map((toast) => (
        <div
          key={toast.id}
          role="alert"
          className="sd-panel sd-panel--thin sd-slide-in flex items-center gap-3 p-3"
        >
          <span className="sd-slot flex h-7 w-7 shrink-0 items-center justify-center">
            {toast.type === 'success' && (
              <img
                src="/images/sd/icon-star.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
            )}
            {toast.type === 'error' && (
              <img
                src="/images/sd/icon-x.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
            )}
            {toast.type === 'info' && (
              <img
                src="/images/sd/icon-scroll.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
            )}
          </span>
          <span className="sd-small flex-1 text-[color:var(--sd-ink)]">
            {toast.message}
          </span>
          <button
            onClick={() => onDismiss(toast.id)}
            className="shrink-0"
            aria-label="Dismiss"
          >
            <img
              src="/images/sd/icon-x.svg"
              width={16}
              height={16}
              className="sd-pixel"
              aria-hidden="true"
            />
          </button>
        </div>
      ))}
    </div>
  )
}
