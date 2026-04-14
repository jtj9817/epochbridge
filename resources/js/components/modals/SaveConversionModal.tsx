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
          <div className="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 className="text-lg font-semibold text-zinc-900 dark:text-zinc-50 mb-3">
              Guest Limit Reached
            </h3>
            <p className="text-sm text-zinc-600 dark:text-zinc-300 mb-4">
              You have reached the guest limit of 3 saved conversions.{' '}
              <a
                href="/register"
                className="font-semibold text-blue-600 dark:text-blue-400 hover:underline"
              >
                Create an account
              </a>{' '}
              for unlimited saves.
            </p>
            <div className="flex justify-end">
              <button
                onClick={onClose}
                className="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg text-sm transition-colors"
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

      <div className="flex items-center justify-center min-h-screen px-4">
        <div className="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-lg w-full">
          {/* Header */}
          <div className="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 className="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
              Save Conversion
            </h3>
            {isGuest && (
              <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                Guest mode: 3 saves maximum
              </p>
            )}
          </div>

          {/* Body */}
          <div className="px-6 py-4">
            <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
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
              className="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-50 resize-none"
            />
            <p className="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
              {note.length}/{MAX_NOTE_LENGTH} characters
            </p>
          </div>

          {/* Footer */}
          <div className="px-6 py-4 flex justify-end gap-3 border-t border-zinc-200 dark:border-zinc-700">
            <button
              onClick={onClose}
              className="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-sm transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={handleSubmit}
              className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
