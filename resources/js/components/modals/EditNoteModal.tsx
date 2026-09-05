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
      <div className="fixed inset-0 sd-overlay" onClick={onClose} />

      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
        <div className="sd-panel sd-pop w-full max-w-lg p-6">
          {/* Header */}
          <div className="sd-panel__title">
            <div className="flex items-center gap-2">
              <img
                src="/images/sd/icon-tome.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
              <h3 className="sd-label text-[color:var(--sd-ink)]">
                {note.trim() === '' ? 'Add Note' : 'Edit Note'}
              </h3>
            </div>
            <button
              onClick={onClose}
              aria-label="Close modal"
              className="sd-btn sd-btn--ghost sd-btn--sm"
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

          {/* Body */}
          <div className="pt-4">
            <textarea
              ref={textareaRef}
              value={note}
              onChange={(e) => setNote(e.target.value)}
              onKeyDown={handleKeyDown}
              rows={6}
              maxLength={MAX_NOTE_LENGTH}
              placeholder="e.g., Birthday, Anniversary, Project Deadline..."
              className="sd-textarea resize-none"
            />
            <div className="mt-2 flex justify-end">
              <p className="sd-data-sm text-[color:var(--sd-ink-soft)]">
                {note.length}/{MAX_NOTE_LENGTH}
              </p>
            </div>
          </div>

          {/* Footer */}
          <div className="mt-4 flex justify-end gap-3">
            <button onClick={onClose} className="sd-btn sd-btn--ghost">
              Cancel
            </button>
            <button onClick={handleSubmit} className="sd-btn sd-btn--primary">
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
