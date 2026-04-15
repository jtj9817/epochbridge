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
        className="fixed inset-0 bg-gray-900/25 transition-opacity"
        onClick={onClose}
      />

      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
        <div className="relative inline-block align-bottom bg-white dark:bg-zinc-900 text-left overflow-hidden border-2 border-theme-300 dark:border-zinc-700 sm:my-8 sm:align-middle sm:max-w-lg w-full">
          {/* Header */}
          <div className="bg-theme-100 dark:bg-zinc-800 px-4 py-3 border-b border-theme-300 dark:border-zinc-700">
            <h3 className="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest">
              {note.trim() === '' ? 'Add Note' : 'Edit Note'}
            </h3>
          </div>

          {/* Body */}
          <div className="bg-white dark:bg-zinc-900 px-4 py-4">
            <textarea
              ref={textareaRef}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              onKeyDown={handleKeyDown}
              rows={6}
              maxLength={MAX_NOTE_LENGTH}
              placeholder="e.g., Birthday, Anniversary, Project Deadline..."
              className="block w-full px-3 py-2 border-2 border-theme-300 dark:border-zinc-600 focus:outline-none focus:border-theme-500 dark:focus:border-theme-100 bg-white dark:bg-zinc-800 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-zinc-500 resize-none font-mono text-sm"
            />
            <div className="mt-2 flex justify-end">
              <p className="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
                {note.length}/{MAX_NOTE_LENGTH}
              </p>
            </div>
          </div>

          {/* Footer */}
          <div className="bg-theme-50 dark:bg-zinc-800 px-4 py-3 flex justify-end gap-3 border-t border-theme-300 dark:border-zinc-700">
            <button
              onClick={onClose}
              className="px-4 py-2 border-2 border-theme-300 dark:border-zinc-600 text-theme-700 dark:text-zinc-300 hover:bg-theme-100 dark:hover:bg-zinc-700 text-xs font-bold uppercase tracking-wider"
            >
              Cancel
            </button>
            <button
              onClick={handleSubmit}
              className="px-4 py-2 bg-theme-500 hover:bg-theme-300 border-2 border-theme-500 hover:border-theme-300 text-white text-xs font-bold uppercase tracking-wider"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
