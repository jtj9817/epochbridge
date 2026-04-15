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
        <h2 className="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest mb-4">
          Your Saved Conversions
        </h2>
        <div className="text-center py-8 text-theme-500 dark:text-theme-100 text-xs uppercase tracking-wider">
          Loading...
        </div>
      </div>
    )
  }

  return (
    <div className="mt-16 max-w-4xl mx-auto">
      <div className="flex items-center justify-between mb-4 pb-4 border-b border-theme-300 dark:border-zinc-700">
        <h2 className="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest">
          Your Saved Conversions
        </h2>
        {currentConversion && (
          <button
            onClick={() => setSaveModalOpen(true)}
            className="text-xs font-medium text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white uppercase tracking-wider"
          >
            Save Current
          </button>
        )}
      </div>

      {/* Migration prompt for newly-authenticated users with guest data */}
      {showMigrationPrompt && (
        <div className="mb-4 p-3 border border-theme-300 dark:border-zinc-700 bg-theme-50 dark:bg-zinc-900">
          <p className="text-xs text-theme-700 dark:text-zinc-300 uppercase tracking-wider">
            You have {guest.items.length} guest conversion
            {guest.items.length !== 1 ? 's' : ''} to migrate.
            <button
              onClick={handleMigration}
              className="ml-2 font-bold underline text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
            >
              Migrate now
            </button>
          </p>
        </div>
      )}

      {migrationStatus === 'done' && (
        <div className="mb-4 p-3 border border-theme-300 dark:border-zinc-700 bg-theme-50 dark:bg-zinc-900">
          <p className="text-xs text-theme-700 dark:text-zinc-300 uppercase tracking-wider">
            Guest conversions migrated successfully!
          </p>
        </div>
      )}

      {migrationStatus === 'error' && (
        <div className="mb-4 p-3 border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20">
          <p className="text-xs text-red-800 dark:text-red-200 uppercase tracking-wider">
            Failed to migrate guest conversions. Please try again.
          </p>
        </div>
      )}

      {/* Conversion Items */}
      {items.length === 0 ? (
        <p className="text-center py-8 text-theme-500 dark:text-theme-100 text-xs uppercase tracking-wider">
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
        <p className="mt-4 text-center text-xs text-theme-500 dark:text-theme-100 uppercase tracking-wider">
          Guest mode: {items.length}/3 saves.{' '}
          <a
            href="/register"
            className="font-bold underline text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
          >
            Register
          </a>{' '}
          for unlimited saves.
        </p>
      )}

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
