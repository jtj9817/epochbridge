import { Head, useForm, usePage, router } from '@inertiajs/react'
import { type FormEvent, useState, useRef, useEffect } from 'react'
import AppLayout from '@/layouts/AppLayout'
import SettingsLayout from '@/layouts/SettingsLayout'
import { useToast } from '@/providers/ToastProvider'

interface TwoFactorPageProps {
  twoFactorEnabled: boolean
  requiresConfirmation: boolean
}

export default function TwoFactorPage({ twoFactorEnabled: initialEnabled, requiresConfirmation }: TwoFactorPageProps) {
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(initialEnabled)
  const [showSetupModal, setShowSetupModal] = useState(false)
  const [showVerificationStep, setShowVerificationStep] = useState(false)
  const [qrCodeSvg, setQrCodeSvg] = useState('')
  const [setupKey, setSetupKey] = useState('')
  const [setupError, setSetupError] = useState('')
  const [showRecoveryCodes, setShowRecoveryCodes] = useState(false)
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([])
  const [recoveryCodesError, setRecoveryCodesError] = useState('')
  const [copiedKey, setCopiedKey] = useState(false)
  const { addToast } = useToast()

  const enableForm = useForm({})
  const confirmForm = useForm({ code: '' })
  const disableForm = useForm({})

  function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]')
    return meta ? (meta as HTMLMetaElement).content : ''
  }

  async function handleEnable() {
    setSetupError('')
    enableForm.post(route('two-factor.enable'), {
      preserveScroll: true,
      onSuccess: () => {
        loadSetupData()
        setShowSetupModal(true)
        setTwoFactorEnabled(requiresConfirmation ? false : true)
      },
      onError: (errors) => {
        const message = Object.values(errors).join(' ')
        setSetupError(message || 'Failed to enable two-factor authentication.')
      },
    })
  }

  async function loadSetupData() {
    try {
      const response = await fetch(route('two-factor.qr'), {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'same-origin',
      })

      if (response.ok) {
        const data = await response.json()
        setQrCodeSvg(data.svg || '')
      }

      const keyResponse = await fetch(route('two-factor.secret-key'), {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'same-origin',
      })

      if (keyResponse.ok) {
        const keyData = await keyResponse.json()
        setSetupKey(keyData.secretKey || '')
      }
    } catch {
      setSetupError('Failed to fetch setup data.')
      setQrCodeSvg('')
      setSetupKey('')
    }
  }

  async function loadRecoveryCodes() {
    try {
      const response = await fetch(route('two-factor.recovery-codes'), {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        credentials: 'same-origin',
      })

      if (response.ok) {
        const data = await response.json()
        setRecoveryCodes(data.recoveryCodes || [])
      }
    } catch {
      setRecoveryCodesError('Failed to load recovery codes.')
    }
  }

  function handleShowVerification() {
    if (requiresConfirmation) {
      setShowVerificationStep(true)
    } else {
      closeModal()
    }
  }

  function handleConfirmTwoFactor(e: FormEvent) {
    e.preventDefault()
    confirmForm.post(route('two-factor.confirm'), {
      preserveScroll: true,
      onSuccess: () => {
        setTwoFactorEnabled(true)
        closeModal()
        loadRecoveryCodes()
        addToast('success', 'Two-factor authentication enabled.')
      },
      onError: (errors) => {
        const message = Object.values(errors).join(' ')
        setSetupError(message || 'Invalid code.')
      },
    })
  }

  function handleRegenerateRecoveryCodes() {
    router.post(route('two-factor.recovery-codes'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        loadRecoveryCodes()
        addToast('success', 'Recovery codes regenerated.')
      },
    })
  }

  function handleDisable() {
    setSetupError('')
    disableForm.delete(route('two-factor.disable'), {
      preserveScroll: true,
      onSuccess: () => {
        setTwoFactorEnabled(false)
        setRecoveryCodes([])
        addToast('success', 'Two-factor authentication disabled.')
      },
      onError: (errors) => {
        const message = Object.values(errors).join(' ')
        setSetupError(message || 'Failed to disable two-factor authentication.')
      },
    })
  }

  function closeModal() {
    setShowSetupModal(false)
    setShowVerificationStep(false)
    setQrCodeSvg('')
    setSetupKey('')
    setSetupError('')
    confirmForm.reset('code')
  }

  function handleCopyKey() {
    navigator.clipboard.writeText(setupKey).then(() => {
      setCopiedKey(true)
      setTimeout(() => setCopiedKey(false), 1500)
    }).catch(() => {
      // Clipboard write failed silently
    })
  }

  useEffect(() => {
    if (twoFactorEnabled) {
      loadRecoveryCodes()
    }
  }, [twoFactorEnabled])

  const modalTitle = twoFactorEnabled
    ? 'Two-Factor Authentication Enabled'
    : showVerificationStep
      ? 'Verify Authentication Code'
      : 'Enable Two-Factor Authentication'

  const modalDescription = twoFactorEnabled
    ? 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'
    : showVerificationStep
      ? 'Enter the 6-digit code from your authenticator app.'
      : 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'

  return (
    <AppLayout title="Two Factor Settings">
      <SettingsLayout
        heading="Two Factor Authentication"
        subheading="Manage your two-factor authentication settings"
      >
        <div className="flex flex-col w-full mx-auto space-y-6 text-sm">
          {setupError && !showSetupModal && (
            <div className="p-3 text-xs text-red-800 dark:text-red-200 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800">
              {setupError}
            </div>
          )}

          {twoFactorEnabled ? (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-800">
                  Enabled
                </span>
              </div>

              <p className="text-sm text-zinc-600 dark:text-zinc-400">
                With two-factor authentication enabled, you will be prompted for a secure, random pin during login,
                which you can retrieve from the TOTP-supported application on your phone.
              </p>

              {/* Recovery codes section */}
              <div className="py-6 space-y-6 border border-zinc-200 dark:border-white/10 shadow-sm rounded-none">
                <div className="px-6 space-y-2">
                  <div className="flex items-center gap-2">
                    <svg className="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <h3 className="text-base font-semibold">2FA Recovery Codes</h3>
                  </div>
                  <p className="text-sm text-zinc-500 dark:text-zinc-400">
                    Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.
                  </p>
                </div>

                <div className="px-6">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button
                      onClick={() => setShowRecoveryCodes(!showRecoveryCodes)}
                      className="btn-primary-technical flex items-center gap-2"
                    >
                      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {showRecoveryCodes ? (
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        ) : (
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        )}
                      </svg>
                      {showRecoveryCodes ? 'Hide Recovery Codes' : 'View Recovery Codes'}
                    </button>

                    {showRecoveryCodes && recoveryCodes.length > 0 && (
                      <button
                        onClick={handleRegenerateRecoveryCodes}
                        className="btn-technical flex items-center gap-2"
                      >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Regenerate Codes
                      </button>
                    )}
                  </div>

                  {showRecoveryCodes && (
                    <div className="mt-3 space-y-3">
                      {recoveryCodesError && (
                        <div className="p-3 text-xs text-red-800 dark:text-red-200 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800">
                          {recoveryCodesError}
                        </div>
                      )}
                      {recoveryCodes.length > 0 && (
                        <>
                          <div className="grid gap-1 p-4 font-mono text-sm bg-zinc-100 dark:bg-white/5">
                            {recoveryCodes.map((code, i) => (
                              <div key={i} className="select-text">{code}</div>
                            ))}
                          </div>
                          <p className="text-xs text-zinc-500 dark:text-zinc-400">
                            Each recovery code can be used once to access your account and will be removed after use.
                            If you need more, click Regenerate Codes above.
                          </p>
                        </>
                      )}
                    </div>
                  )}
                </div>
              </div>

              <div className="flex justify-start">
                <button
                  onClick={handleDisable}
                  disabled={disableForm.processing}
                  className="btn-technical border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
                >
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  Disable 2FA
                </button>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-800">
                  Disabled
                </span>
              </div>

              <p className="text-sm text-zinc-500 dark:text-zinc-400">
                When you enable two-factor authentication, you will be prompted for a secure pin during login.
                This pin can be retrieved from a TOTP-supported application on your phone.
              </p>

              <button
                onClick={handleEnable}
                disabled={enableForm.processing}
                className="btn-primary-technical flex items-center gap-2"
              >
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Enable 2FA
              </button>
            </div>
          )}
        </div>

        {/* Setup Modal */}
        {showSetupModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="absolute inset-0 bg-black/50" onClick={closeModal} />
            <div className="relative w-full max-w-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
              <div className="space-y-6">
                {/* Modal header */}
                <div className="flex flex-col items-center space-y-4">
                  <div className="p-0.5 w-auto rounded-full border border-stone-100 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm">
                    <div className="p-2.5 rounded-full border border-stone-200 dark:border-stone-600 overflow-hidden bg-stone-100 dark:bg-stone-200 relative">
                      <svg className="w-8 h-8 text-zinc-600 dark:text-accent-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                      </svg>
                    </div>
                  </div>
                  <div className="space-y-2 text-center">
                    <h3 className="text-lg font-semibold">{modalTitle}</h3>
                    <p className="text-sm text-zinc-600 dark:text-zinc-400">{modalDescription}</p>
                  </div>
                </div>

                {showVerificationStep ? (
                  /* Verification step */
                  <div className="space-y-6">
                    <div className="flex flex-col items-center space-y-3">
                      <div className="flex gap-2">
                        {Array.from({ length: 6 }).map((_, i) => (
                          <input
                            key={i}
                            type="text"
                            maxLength={1}
                            value={confirmForm.data.code[i] || ''}
                            onChange={(e) => {
                              const val = e.target.value.replace(/\D/g, '')
                              const currentCode = confirmForm.data.code.split('')
                              currentCode[i] = val.slice(-1)
                              const newCode = currentCode.join('')
                              confirmForm.setData('code', newCode)
                              if (val && i < 5) {
                                const next = e.target.nextElementSibling as HTMLInputElement
                                next?.focus()
                              }
                            }}
                            onKeyDown={(e) => {
                              if (e.key === 'Backspace' && !confirmForm.data.code[i] && i > 0) {
                                const prev = (e.target as HTMLElement).previousElementSibling as HTMLInputElement
                                prev?.focus()
                              }
                            }}
                            className="w-10 h-12 text-center text-lg border-2 border-theme-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 font-mono"
                            autoFocus={i === 0}
                            autoComplete="one-time-code"
                          />
                        ))}
                      </div>
                      {confirmForm.errors.code && (
                        <p className="text-xs text-red-600 dark:text-red-400">{confirmForm.errors.code}</p>
                      )}
                    </div>

                    <div className="flex items-center space-x-3">
                      <button
                        onClick={() => { setShowVerificationStep(false); confirmForm.reset('code'); setSetupError('') }}
                        className="btn-technical flex-1"
                      >
                        Back
                      </button>
                      <button
                        onClick={handleConfirmTwoFactor}
                        disabled={confirmForm.data.code.length < 6 || confirmForm.processing}
                        className="btn-primary-technical flex-1 disabled:opacity-75 disabled:cursor-not-allowed"
                      >
                        Confirm
                      </button>
                    </div>
                  </div>
                ) : (
                  /* QR code and setup key step */
                  <>
                    {setupError && (
                      <div className="p-3 text-xs text-red-800 dark:text-red-200 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800">
                        {setupError}
                      </div>
                    )}

                    <div className="flex justify-center">
                      <div className="relative w-64 overflow-hidden border border-stone-200 dark:border-stone-700 aspect-square">
                        {qrCodeSvg ? (
                          <div
                            className="flex items-center justify-center h-full p-4"
                            dangerouslySetInnerHTML={{ __html: qrCodeSvg }}
                          />
                        ) : (
                          <div className="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700 animate-pulse">
                            <svg className="w-6 h-6 text-zinc-400 animate-spin" fill="none" viewBox="0 0 24 24">
                              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                          </div>
                        )}
                      </div>
                    </div>

                    <div>
                      <button
                        onClick={handleShowVerification}
                        disabled={!!setupError}
                        className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
                      >
                        {twoFactorEnabled ? 'Close' : 'Continue'}
                      </button>
                    </div>

                    <div className="space-y-4">
                      <div className="relative flex items-center justify-center w-full">
                        <div className="absolute inset-0 w-full h-px top-1/2 bg-stone-200 dark:bg-stone-600" />
                        <span className="relative px-2 text-sm bg-white dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                          or, enter the code manually
                        </span>
                      </div>

                      <div className="flex items-stretch w-full border dark:border-stone-700">
                        {setupKey ? (
                          <>
                            <input
                              type="text"
                              readOnly
                              value={setupKey}
                              className="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100 font-mono text-xs"
                            />
                            <button
                              onClick={handleCopyKey}
                              className="px-3 transition-colors border-l border-stone-200 dark:border-stone-600 cursor-pointer"
                            >
                              {copiedKey ? (
                                <svg className="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                              ) : (
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                              )}
                            </button>
                          </>
                        ) : (
                          <div className="flex items-center justify-center w-full p-3 bg-zinc-100 dark:bg-stone-700">
                            <svg className="w-4 h-4 text-zinc-400 animate-spin" fill="none" viewBox="0 0 24 24">
                              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                          </div>
                        )}
                      </div>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        )}
      </SettingsLayout>
    </AppLayout>
  )
}
