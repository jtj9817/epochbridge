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
        <div className="fixed inset-0 sd-overlay" onClick={onClose} />
        <div className="flex items-center justify-center min-h-screen px-4">
          <div
            className="sd-panel sd-panel--thin sd-pop w-full max-w-md p-6"
            style={{ borderImageSource: 'var(--sd-frame-gold)' }}
          >
            <div className="mb-3 flex items-center gap-2">
              <img
                src="/images/sd/icon-coin.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
              <h3 className="sd-h3 text-[color:var(--sd-ink)]">
                Guest Limit Reached
              </h3>
            </div>
            <p className="sd-small mb-4 text-[color:var(--sd-ink-soft)]">
              You have reached the guest limit of 3 saved conversions.{' '}
              <a
                href="/register"
                className="font-bold underline text-[color:var(--sd-gold-text)]"
              >
                Create an account
              </a>{' '}
              for unlimited saves.
            </p>
            <div className="flex justify-end">
              <button onClick={onClose} className="sd-btn sd-btn--gold">
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
      <div className="fixed inset-0 sd-overlay" onClick={onClose} />

      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
        <div className="sd-panel sd-pop w-full max-w-lg p-6">
          {/* Header */}
          <div className="sd-panel__title">
            <div className="flex items-center gap-2">
              <img
                src="/images/sd/icon-floppy.svg"
                width={16}
                height={16}
                className="sd-pixel"
                aria-hidden="true"
              />
              <h3 className="sd-h3 text-[color:var(--sd-ink)]">
                Save Conversion
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
            {isGuest && (
              <p className="sd-small mb-3 text-[color:var(--sd-ink-soft)]">
                Guest mode: 3 saves maximum
              </p>
            )}
            <label className="sd-label mb-2 block text-[color:var(--sd-ink-soft)]">
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
              className="sd-textarea resize-none"
            />
            <p className="sd-data-sm mt-1 text-right text-[color:var(--sd-ink-soft)]">
              {note.length}/{MAX_NOTE_LENGTH} characters
            </p>
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
