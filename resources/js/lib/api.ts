import type {
  SavedConversion,
  SavedConversionListResponse,
  SavedConversionResponse,
  GuestSavedConversion,
} from '@/types'

function getCsrfToken(): string {
  return (
    document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content') || ''
  )
}

function apiHeaders(): HeadersInit {
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': getCsrfToken(),
  }
}

/**
 * Fetch all saved conversions for the authenticated user.
 */
export async function fetchSavedConversions(): Promise<SavedConversion[]> {
  const response = await fetch('/frontend/saved-conversions', {
    headers: apiHeaders(),
  })

  if (!response.ok) {
    throw new Error('Failed to fetch saved conversions.')
  }

  const data: SavedConversionListResponse = await response.json()
  return data.data
}

/**
 * Save a new conversion for the authenticated user.
 */
export async function createSavedConversion(payload: {
  original_input: string
  conversion_data: Record<string, unknown>
  note?: string
}): Promise<SavedConversionResponse> {
  const response = await fetch('/frontend/saved-conversions', {
    method: 'POST',
    headers: apiHeaders(),
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.error || error.message || 'Failed to save conversion.')
  }

  return response.json()
}

/**
 * Delete a saved conversion.
 */
export async function deleteSavedConversion(
  id: number
): Promise<void> {
  const response = await fetch(`/frontend/saved-conversions/${id}`, {
    method: 'DELETE',
    headers: apiHeaders(),
  })

  if (!response.ok) {
    throw new Error('Failed to delete conversion.')
  }
}

/**
 * Update the note on a saved conversion.
 */
export async function updateSavedConversionNote(
  id: number,
  note: string
): Promise<SavedConversionResponse> {
  const response = await fetch(
    `/frontend/saved-conversions/${id}/note`,
    {
      method: 'PATCH',
      headers: apiHeaders(),
      body: JSON.stringify({ note }),
    }
  )

  if (!response.ok) {
    throw new Error('Failed to update note.')
  }

  return response.json()
}

/**
 * Migrate guest conversions to the authenticated user's account.
 */
export async function migrateGuestConversions(
  items: GuestSavedConversion[]
): Promise<void> {
  const response = await fetch('/frontend/saved-conversions/migrate', {
    method: 'POST',
    headers: apiHeaders(),
    body: JSON.stringify({ items }),
  })

  if (!response.ok) {
    throw new Error('Failed to migrate guest conversions.')
  }
}
