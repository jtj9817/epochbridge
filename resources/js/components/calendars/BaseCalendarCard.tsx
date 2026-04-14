import { ReactNode, useState } from 'react'

interface BaseCalendarCardProps {
  title: string
  copyText?: string
  copyType: string
  disclaimer?: string
  className?: string
  icon?: ReactNode
  children: ReactNode
}

export function BaseCalendarCard({
  title,
  copyText,
  copyType,
  disclaimer,
  className = '',
  icon,
  children,
}: BaseCalendarCardProps) {
  const [copiedFeedback, setCopiedFeedback] = useState<string | null>(null)
  const [showDisclaimer, setShowDisclaimer] = useState(false)

  const copyToClipboard = async (text: string, type: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedFeedback(type)
      setTimeout(() => setCopiedFeedback(null), 3000)
    } catch (err) {
      console.error('Failed to copy:', err)
    }
  }

  return (
    <div
      className={`calendar-card bg-white dark:bg-zinc-900 border border-theme-300 dark:border-zinc-700 flex flex-col ${className}`}
    >
      <div className="calendar-card-header border-b border-theme-300 dark:border-zinc-700 flex items-center justify-between">
        <h3 className="flex items-center gap-2 text-xs font-bold text-zinc-700 dark:text-zinc-200 uppercase tracking-wider">
          {icon}
          {title}
        </h3>
        <div className="flex items-center gap-2">
          {disclaimer && (
            <div className="relative">
              <button
                onMouseEnter={() => setShowDisclaimer(true)}
                onMouseLeave={() => setShowDisclaimer(false)}
                className="text-zinc-500 dark:text-zinc-400"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </button>
              {showDisclaimer && (
                <div className="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-900 dark:bg-zinc-800 text-white border border-zinc-700 z-10">
                  {disclaimer}
                </div>
              )}
            </div>
          )}
          {copyText && (
            <button
              onClick={() => copyToClipboard(copyText, copyType)}
              title="Copy"
              className="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors"
            >
              {copiedFeedback === copyType ? (
                <span className="text-green-500 text-xs uppercase tracking-wider">
                  OK
                </span>
              ) : (
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
              )}
            </button>
          )}
        </div>
      </div>
      <div className="calendar-card-content flex-grow flex flex-col items-center justify-center text-center">
        {children}
      </div>
    </div>
  )
}
