import { useState, useCallback } from 'react'
import { type InputType } from '@/types'

interface CalendarQuickActionsProps {
  inputType: InputType
  onDateSelect: (date: string) => void
}

type QuickAction = 'today' | 'yesterday' | 'tomorrow'

export function CalendarQuickActions({
  inputType,
  onDateSelect,
}: CalendarQuickActionsProps) {
  const [loadingAction, setLoadingAction] = useState<QuickAction | null>(null)

  const handleQuickAction = useCallback(
    async (action: QuickAction) => {
      setLoadingAction(action)

      try {
        const params = new URLSearchParams({
          action,
          input_type: inputType,
        })

        const response = await fetch(`/frontend/quick-action?${params}`, {
          method: 'GET',
          headers: {
            Accept: 'application/json',
          },
        })

        if (!response.ok) {
          console.error('Quick action failed:', response.statusText)
          return
        }

        const data = await response.json()
        onDateSelect(data.date)
      } catch (err) {
        console.error('Quick action error:', err)
      } finally {
        setLoadingAction(null)
      }
    },
    [inputType, onDateSelect]
  )

  return (
    <div
      id="quick-action-buttons"
      className="flex flex-col sm:flex-row gap-2 justify-center mb-6"
    >
      <button
        id="btn-today"
        onClick={() => handleQuickAction('today')}
        disabled={loadingAction !== null}
        className="sd-btn sd-btn--ghost sd-btn--sm"
      >
        {loadingAction === 'today' ? '...' : 'TODAY'}
      </button>
      <button
        id="btn-yesterday"
        onClick={() => handleQuickAction('yesterday')}
        disabled={loadingAction !== null}
        className="sd-btn sd-btn--ghost sd-btn--sm"
      >
        {loadingAction === 'yesterday' ? '...' : 'YESTERDAY'}
      </button>
      <button
        id="btn-tomorrow"
        onClick={() => handleQuickAction('tomorrow')}
        disabled={loadingAction !== null}
        className="sd-btn sd-btn--ghost sd-btn--sm"
      >
        {loadingAction === 'tomorrow' ? '...' : 'TOMORROW'}
      </button>
    </div>
  )
}
