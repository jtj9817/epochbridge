/**
 * Saved conversion types for the React frontend.
 */

/**
 * A single saved conversion as returned by the API.
 */
export interface SavedConversion {
  id: number
  original_input: string
  note: string
  conversion_data: Record<string, unknown>
  uses_legacy_fallback: boolean
  created_at: string | null
}

/**
 * A guest saved conversion stored in localStorage.
 */
export interface GuestSavedConversion {
  id: number
  original_input: string
  conversion_data: Record<string, unknown>
  note: string
  created_at: string
}

/**
 * Payload for migrating guest conversions to the server.
 */
export interface MigrateGuestConversionsPayload {
  items: GuestSavedConversion[]
}

/**
 * API response for listing saved conversions.
 */
export interface SavedConversionListResponse {
  data: SavedConversion[]
}

/**
 * API response for creating/updating a saved conversion.
 */
export interface SavedConversionResponse {
  id: number
  original_input: string
  note: string
  conversion_data: Record<string, unknown>
  uses_legacy_fallback: boolean
  created_at: string | null
}
