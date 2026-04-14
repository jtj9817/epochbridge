import { useState, useEffect, useRef } from 'react'

const MAX_NOTE_LENGTH = 2048

interface EditNoteModalProps {
  open: boolean
  initialValue: string
  onSave: (note: string) => void
  onClose: () => void
}

export function EditNoteModal({
  open,
  initialValue,
  onSave,
  onClose,
}: EditNoteModalProps) {
  const [note, setNote] = useState(initialValue)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  useEffect(() => {
    if (open) {
      setNote(initialValue)
      setTimeout(() => textareaRef.current?.focus(), 50)
    }
  }, [open, initialValue])

  if (!open) return null

  const handleSubmit = () => {
    const trimmed = note.trim().slice(0, MAX_NOTE_LENGTH)
    onSave(trimmed)
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      onClose()
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
      handleSubmit()
    }
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
              Edit Note
            </h3>
          </div>

          {/* Body */}
          <div className="px-6 py-4">
            <textarea
              ref={textareaRef}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              onKeyDown={handleKeyDown}
              rows={4}
              maxLength={MAX_NOTE_LENGTH}
              placeholder="Add a note for this conversion..."
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
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors"
            >
              Save Note
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
