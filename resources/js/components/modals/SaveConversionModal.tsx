import { useState, useRef, useEffect } from 'react'

const MAX_NOTE_LENGTH = 2048

interface SaveConversionModalProps {
  open: boolean
  onClose: () => void
  onSave: (note: string) => void
  isGuest: boolean
  guestLimitReached: boolean
}

export function SaveConversionModal({
  open,
  onClose,
  onSave,
  isGuest,
  guestLimitReached,
}: SaveConversionModalProps) {
  const [note, setNote] = useState('')
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  useEffect(() => {
    if (open) {
      setNote('')
    }
  }, [open])

  useEffect(() => {
    if (open) {
      setTimeout(() => textareaRef.current?.focus(), 50)
    }
  }, [open])

  if (!open) return null

  const handleSubmit = () => {
    onSave(note.trim())
    setNote('')
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      onClose()
    }
  }

  if (guestLimitReached) {
    return (
      <div
        className="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
      >
        <div
          className="fixed inset-0 bg-black/50 transition-opacity"
          onClick={onClose}
        />
        <div className="flex items-center justify-center min-h-screen px-4">
          <div className="relative bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-700 max-w-md w-full p-6">
            <h3 className="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest mb-3">
              Guest Limit Reached
            </h3>
            <p className="text-xs text-theme-700 dark:text-zinc-300 uppercase tracking-wider mb-4">
              You have reached the guest limit of 3 saved conversions.{' '}
              <a
                href="/register"
                className="font-bold underline text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
              >
                Create an account
              </a>{' '}
              for unlimited saves.
            </p>
            <div className="flex justify-end">
              <button
                onClick={onClose}
                className="px-4 py-2 bg-theme-500 hover:bg-theme-300 border-2 border-theme-500 hover:border-theme-300 text-white text-xs font-bold uppercase tracking-wider"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div
      className="fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <div
        className="fixed inset-0 bg-black/50 transition-opacity"
        onClick={onClose}
      />

      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          {/* Header */}
          <div className="bg-theme-100 dark:bg-zinc-700 px-6 py-4 border-b border-theme-200 dark:border-zinc-600">
            <h3 className="text-lg font-semibold text-theme-900 dark:text-theme-50">
              Save Conversion
            </h3>
            {isGuest && (
              <p className="text-xs text-theme-500 dark:text-theme-300 mt-1">
                Guest mode: 3 saves maximum
              </p>
            )}
          </div>

          {/* Body */}
          <div className="bg-white dark:bg-zinc-800 px-6 py-4">
            <label className="block text-sm font-medium text-theme-700 dark:text-theme-300 mb-2">
              Add a note (optional)
            </label>
            <textarea
              ref={textareaRef}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              onKeyDown={handleKeyDown}
              rows={3}
              maxLength={MAX_NOTE_LENGTH}
              placeholder="e.g., Birthday, Anniversary, Project Deadline..."
              className="w-full px-3 py-2 border border-theme-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-theme-500 text-sm resize-none"
            />
            <p className="mt-1 text-xs text-theme-500 dark:text-theme-400">
              {note.length}/{MAX_NOTE_LENGTH} characters
            </p>
          </div>

          {/* Footer */}
          <div className="bg-theme-50 dark:bg-zinc-900 px-6 py-4 flex justify-end gap-3">
            <button
              onClick={onClose}
              className="px-4 py-2 border border-theme-300 dark:border-zinc-600 rounded-lg text-theme-700 dark:text-theme-300 hover:bg-theme-100 dark:hover:bg-zinc-800 transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={handleSubmit}
              className="px-4 py-2 bg-theme-500 hover:bg-theme-600 text-white rounded-lg transition-colors"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
