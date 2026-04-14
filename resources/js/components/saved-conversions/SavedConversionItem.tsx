import { useState, useCallback } from 'react'
import { EditNoteModal } from '../modals/EditNoteModal'

interface ConversionItemCommon {
  id: number
  original_input: string
  note: string
  conversion_data: Record<string, unknown>
  created_at: string | null
  isGuest: boolean
  uses_legacy_fallback?: boolean
}

interface SavedConversionItemProps {
  item: ConversionItemCommon
  onDelete: (id: number) => void
  onUpdateNote: (id: number, note: string) => void
}

export function SavedConversionItem({
  item,
  onDelete,
  onUpdateNote,
}: SavedConversionItemProps) {
  const [collapsed, setCollapsed] = useState(true)
  const [noteModalOpen, setNoteModalOpen] = useState(false)
  const [copiedField, setCopiedField] = useState<string | null>(null)

  const conversionData = item.conversion_data as Record<
    string,
    {
      name?: string
      full_date?: string
      value?: string | number
      tags?: string[]
    }
  >

  const copyValue = useCallback(
    async (text: string, field: string) => {
      try {
        await navigator.clipboard.writeText(text)
        setCopiedField(field)
        setTimeout(() => setCopiedField(null), 3000)
      } catch (err) {
        console.error('Failed to copy:', err)
      }
    },
    []
  )

  const createdAt = item.created_at
    ? new Date(item.created_at).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    : ''

  const primaryEntries = Object.entries(conversionData)
    .filter(([, v]) => v && typeof v === 'object' && 'full_date' in v)
    .slice(0, 6)

  const hasLegacyIndicator =
    !item.isGuest && 'uses_legacy_fallback' in item && item.uses_legacy_fallback

  return (
    <>
      <div
        className="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden"
        data-testid={`saved-conversion-${item.id}`}
      >
        {/* Header */}
        <button
          onClick={() => setCollapsed(!collapsed)}
          className="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-750 transition-colors text-left"
        >
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-zinc-900 dark:text-zinc-50 truncate">
              {item.original_input}
            </p>
            <div className="flex items-center gap-2 mt-0.5">
              <p className="text-xs text-zinc-500 dark:text-zinc-400">
                {createdAt}
              </p>
              {hasLegacyIndicator && (
                <span className="text-xs px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">
                  Legacy
                </span>
              )}
            </div>
          </div>
          <svg
            className={`w-4 h-4 text-zinc-400 transition-transform ${collapsed ? '' : 'rotate-180'}`}
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>

        {/* Collapsible Content */}
        {!collapsed && (
          <div className="border-t border-zinc-200 dark:border-zinc-700">
            {/* Calendar Data Table */}
            <div className="px-4 py-2 bg-white dark:bg-zinc-800">
              <table className="w-full text-sm">
                <tbody>
                  {primaryEntries.map(([key, data]) => (
                    <tr key={key} className="border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                      <td className="py-1.5 font-medium text-zinc-700 dark:text-zinc-300 w-32">
                        {data.name || key}
                      </td>
                      <td className="py-1.5 text-zinc-900 dark:text-zinc-50">
                        {data.full_date ?? 'N/A'}
                      </td>
                      <td className="py-1.5 w-16 text-right">
                        <button
                          onClick={() =>
                            copyValue(
                              String(data.full_date ?? ''),
                              key
                            )
                          }
                          className="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        >
                          {copiedField === key ? 'Copied' : 'Copy'}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Note Section */}
            <div className="px-4 py-3 bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700">
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <svg
                      className="w-4 h-4 text-zinc-400"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                      />
                    </svg>
                    <span className="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                      Note
                    </span>
                  </div>
                  <p className="text-sm text-zinc-600 dark:text-zinc-300 whitespace-pre-wrap">
                    {item.note || 'No note added'}
                  </p>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setNoteModalOpen(true)}
                    className="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    title="Edit note"
                  >
                    <svg
                      className="w-4 h-4"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                  </button>
                  <button
                    onClick={() => onDelete(item.id)}
                    className="p-1.5 text-red-400 hover:text-red-600 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    title="Delete conversion"
                  >
                    <svg
                      className="w-4 h-4"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      <EditNoteModal
        open={noteModalOpen}
        initialValue={item.note}
        onSave={(note) => {
          onUpdateNote(item.id, note)
          setNoteModalOpen(false)
        }}
        onClose={() => setNoteModalOpen(false)}
      />
    </>
  )
}
