import { useCallback, useState } from 'react'
import type { SavedConversion } from '@/types'
import { useGuestSavedConversions } from '@/hooks/useGuestSavedConversions'
import { useToast } from '@/providers/ToastProvider'
import { migrateGuestConversions } from '@/lib/api'
import { SavedConversionItem } from './SavedConversionItem'
import { SaveConversionModal } from '../modals/SaveConversionModal'

interface SavedConversionsSectionProps {
  isAuthenticated: boolean
  authItems: SavedConversion[]
  authLoading: boolean
  onAuthSave: (
    originalInput: string,
    conversionData: Record<string, unknown>,
    note?: string
  ) => Promise<SavedConversion | null>
  onAuthDelete: (id: number) => Promise<void>
  onAuthUpdateNote: (id: number, note: string) => Promise<void>
  currentConversion: {
    originalInput: string
    conversionData: Record<string, unknown>
  } | null
}

export function SavedConversionsSection({
  isAuthenticated,
  authItems,
  authLoading,
  onAuthSave,
  onAuthDelete,
  onAuthUpdateNote,
  currentConversion,
}: SavedConversionsSectionProps) {
  const guest = useGuestSavedConversions(!isAuthenticated)
  const { addToast } = useToast()
  const [saveModalOpen, setSaveModalOpen] = useState(false)
  const [migrationStatus, setMigrationStatus] = useState<
    'idle' | 'migrating' | 'done' | 'error'
  >('idle')

  const items = isAuthenticated ? authItems : guest.items

  const handleSaveFromModal = useCallback(
    async (note: string) => {
      if (!currentConversion) return

      if (isAuthenticated) {
        const result = await onAuthSave(
          currentConversion.originalInput,
          currentConversion.conversionData,
          note
        )
        if (result) {
          addToast('success', 'Conversion saved successfully!')
        } else {
          addToast('error', 'Failed to save conversion.')
        }
      } else {
        const saved = guest.save(
          currentConversion.originalInput,
          currentConversion.conversionData,
          note
        )
        if (saved) {
          addToast('success', 'Conversion saved! (Guest mode: 3 max)')
        } else {
          addToast('info', 'Conversion already saved or limit reached.')
        }
      }

      setSaveModalOpen(false)
    },
    [isAuthenticated, currentConversion, onAuthSave, guest, addToast]
  )

  const handleDelete = useCallback(
    async (id: number) => {
      if (isAuthenticated) {
        try {
          await onAuthDelete(id)
          addToast('success', 'Conversion deleted successfully!')
        } catch {
          addToast('error', 'Failed to delete conversion.')
        }
      } else {
        guest.remove(id)
        addToast('success', 'Conversion deleted successfully!')
      }
    },
    [isAuthenticated, onAuthDelete, guest, addToast]
  )

  const handleUpdateNote = useCallback(
    async (id: number, note: string) => {
      if (isAuthenticated) {
        try {
          await onAuthUpdateNote(id, note)
          addToast('success', 'Note updated!')
        } catch {
          addToast('error', 'Failed to update note.')
        }
      } else {
        guest.updateNote(id, note)
        addToast('success', 'Note updated!')
      }
    },
    [isAuthenticated, onAuthUpdateNote, guest, addToast]
  )

  const handleMigration = useCallback(async () => {
    const guestItems = guest.getItemsForMigration()
    if (guestItems.length === 0) return

    setMigrationStatus('migrating')

    try {
      await migrateGuestConversions(guestItems)
      guest.clearAll()
      setMigrationStatus('done')
      addToast('success', 'Guest conversions migrated successfully!')
    } catch {
      setMigrationStatus('error')
      addToast('error', 'Failed to migrate guest conversions.')
    }
  }, [guest, addToast])

  const showMigrationPrompt =
    isAuthenticated && migrationStatus === 'idle' && guest.items.length > 0

  if (authLoading && isAuthenticated) {
    return (
      <div className="mt-16 max-w-4xl mx-auto">
        <div className="sd-panel">
          <div className="sd-panel__title">
            <h2 className="sd-h3 text-[color:var(--sd-ink)]">
              Your Saved Conversions
            </h2>
          </div>
          <p className="sd-label text-center py-6 text-[color:var(--sd-ink-soft)]">
            Loading...
          </p>
        </div>
      </div>
    )
  }

  return (
    <div className="mt-16 max-w-4xl mx-auto">
      <div className="sd-panel">
        <div className="sd-panel__title">
          <h2 className="sd-h3 text-[color:var(--sd-ink)]">
            Your Saved Conversions
          </h2>
          {currentConversion && (
            <button
              onClick={() => setSaveModalOpen(true)}
              className="sd-btn sd-btn--primary sd-btn--sm"
              data-test="save-current-button"
            >
              <img
                src="/images/sd/icon-floppy.svg"
                width={14}
                height={14}
                className="sd-pixel"
                aria-hidden="true"
              />
              Save Current
            </button>
          )}
        </div>

        <div className="p-4">
          {/* Migration prompt for newly-authenticated users with guest data */}
          {showMigrationPrompt && (
            <div
              className="sd-panel sd-panel--thin p-3 mb-4"
              style={{
                background:
                  'color-mix(in srgb, var(--sd-gold) 25%, var(--sd-parch))',
              }}
            >
              <p className="sd-label text-[color:var(--sd-ink)]">
                You have {guest.items.length} guest conversion
                {guest.items.length !== 1 ? 's' : ''} to migrate.
                <button
                  onClick={handleMigration}
                  className="sd-btn sd-btn--gold sd-btn--sm ml-2 align-middle"
                >
                  Migrate now
                </button>
              </p>
            </div>
          )}

          {migrationStatus === 'done' && (
            <div className="sd-panel sd-panel--thin p-3 mb-4">
              <p className="sd-label text-[color:var(--sd-ink)]">
                Guest conversions migrated successfully!
              </p>
            </div>
          )}

          {migrationStatus === 'error' && (
            <div
              className="sd-panel sd-panel--thin p-3 mb-4"
              style={{
                background:
                  'color-mix(in srgb, var(--sd-red) 14%, var(--sd-parch))',
              }}
            >
              <p className="sd-label text-[color:var(--sd-ink)]">
                Failed to migrate guest conversions. Please try again.
              </p>
            </div>
          )}

          {/* Conversion Items */}
          {items.length === 0 ? (
            <p className="sd-label text-center py-6 text-[color:var(--sd-ink-soft)]">
              No saved conversions yet.
            </p>
          ) : (
            <div className="space-y-4">
              {items.map((item) => (
                <SavedConversionItem
                  key={item.id}
                  item={
                    isAuthenticated
                      ? { ...item, isGuest: false }
                      : { ...item, isGuest: true }
                  }
                  onDelete={handleDelete}
                  onUpdateNote={handleUpdateNote}
                />
              ))}
            </div>
          )}

          {/* Guest limit notice */}
          {!isAuthenticated && items.length > 0 && (
            <p className="mt-4 text-center sd-label text-[color:var(--sd-ink-soft)]">
              Guest mode: {items.length}/3 saves.{' '}
              <a
                href="/register"
                className="font-bold underline text-[color:var(--sd-gold-text)]"
              >
                Register
              </a>{' '}
              for unlimited saves.
            </p>
          )}
        </div>
      </div>

      <SaveConversionModal
        open={saveModalOpen}
        onClose={() => setSaveModalOpen(false)}
        onSave={handleSaveFromModal}
        isGuest={!isAuthenticated}
        guestLimitReached={!isAuthenticated && guest.saveLimitReached}
      />
    </div>
  )
}
