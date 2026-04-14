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
      <div className="mt-12 max-w-4xl mx-auto">
        <h2 className="text-sm font-bold text-zinc-900 dark:text-zinc-50 uppercase tracking-widest mb-4">
          Saved Conversions
        </h2>
        <div className="text-center py-8 text-zinc-400 text-xs uppercase tracking-wider">
          Loading...
        </div>
      </div>
    )
  }

  return (
    <div className="mt-12 max-w-4xl mx-auto">
      <div className="flex items-center justify-between mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        <h2 className="text-sm font-bold text-zinc-900 dark:text-zinc-50 uppercase tracking-widest">
          Saved Conversions ({items.length})
        </h2>
        {currentConversion && (
          <button
            onClick={() => setSaveModalOpen(true)}
            className="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 uppercase tracking-wider"
          >
            Save Current
          </button>
        )}
      </div>

      {/* Migration prompt for newly-authenticated users with guest data */}
      {showMigrationPrompt && (
        <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <p className="text-sm text-blue-800 dark:text-blue-200">
            You have {guest.items.length} guest conversion
            {guest.items.length !== 1 ? 's' : ''} to migrate.
            <button
              onClick={handleMigration}
              className="ml-2 font-semibold underline hover:no-underline"
            >
              Migrate now
            </button>
          </p>
        </div>
      )}

      {migrationStatus === 'done' && (
        <div className="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
          <p className="text-sm text-green-800 dark:text-green-200">
            Guest conversions migrated successfully!
          </p>
        </div>
      )}

      {migrationStatus === 'error' && (
        <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
          <p className="text-sm text-red-800 dark:text-red-200">
            Failed to migrate guest conversions. Please try again.
          </p>
        </div>
      )}

      {/* Conversion Items */}
      {items.length === 0 ? (
        <p className="text-center py-8 text-zinc-400 text-xs uppercase tracking-wider">
          No saved conversions yet.
        </p>
      ) : (
        <div className="space-y-3">
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
        <p className="mt-4 text-center text-xs text-zinc-400 dark:text-zinc-500">
          Guest mode: {items.length}/3 saves.{' '}
          <a
            href="/register"
            className="text-blue-500 hover:underline"
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
