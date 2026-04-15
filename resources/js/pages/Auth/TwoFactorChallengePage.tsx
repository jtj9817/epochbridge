import { Head, useForm } from '@inertiajs/react'
import { type FormEvent, useState, useEffect, useRef } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

interface TwoFactorChallengePageProps {
  recovery: boolean
}

export default function TwoFactorChallengePage({ recovery }: TwoFactorChallengePageProps) {
  const [showRecoveryInput, setShowRecoveryInput] = useState(recovery)
  const codeInputRef = useRef<HTMLInputElement>(null)
  const recoveryInputRef = useRef<HTMLInputElement>(null)

  const { data, setData, post, processing, errors } = useForm({
    code: '',
    recovery_code: '',
  })

  useEffect(() => {
    if (showRecoveryInput) {
      recoveryInputRef.current?.focus()
    } else {
      codeInputRef.current?.focus()
    }
  }, [showRecoveryInput])

  function submit(e: FormEvent) {
    e.preventDefault()
    post(route('two-factor.login.store'))
  }

  function toggleInput() {
    setShowRecoveryInput((prev) => !prev)
    setData('code', '')
    setData('recovery_code', '')
  }

  return (
    <AuthLayout title="Two-Factor Authentication">
      <div className="flex flex-col gap-6">
        {!showRecoveryInput ? (
          <div className="text-center">
            <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
              Authentication Code
            </h2>
            <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
              Enter the authentication code provided by your authenticator application.
            </p>
          </div>
        ) : (
          <div className="text-center">
            <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
              Recovery Code
            </h2>
            <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
              Please confirm access to your account by entering one of your emergency recovery codes.
            </p>
          </div>
        )}

        <form onSubmit={submit}>
          <div className="space-y-5 text-center">
            {!showRecoveryInput ? (
              <div>
                <div className="flex items-center justify-center my-5 gap-2">
                  {Array.from({ length: 6 }).map((_, i) => (
                    <input
                      key={i}
                      ref={i === 0 ? codeInputRef : undefined}
                      type="text"
                      maxLength={1}
                      value={data.code[i] || ''}
                      onChange={(e) => {
                        const val = e.target.value
                        const newCode = data.code.split('')
                        newCode[i] = val
                        setData('code', newCode.join(''))
                        if (val && i < 5) {
                          const next = e.target.nextElementSibling as HTMLInputElement | null
                          next?.focus()
                        }
                      }}
                      className="w-12 h-14 text-center text-lg font-bold input-technical"
                      autoComplete="one-time-code"
                    />
                  ))}
                </div>
                {errors.code && (
                  <p className="text-xs text-red-600 dark:text-red-400">{errors.code}</p>
                )}
              </div>
            ) : (
              <div>
                <div className="my-5">
                  <input
                    ref={recoveryInputRef}
                    type="text"
                    value={data.recovery_code}
                    onChange={(e) => setData('recovery_code', e.target.value)}
                    required={showRecoveryInput}
                    autoComplete="one-time-code"
                    className="input-technical w-full text-center font-mono"
                    placeholder="Recovery code"
                  />
                </div>
                {errors.recovery_code && (
                  <p className="text-xs text-red-600 dark:text-red-400">{errors.recovery_code}</p>
                )}
              </div>
            )}

            <button
              type="submit"
              disabled={processing}
              className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
            >
              {processing ? 'VERIFYING...' : 'CONTINUE'}
            </button>
          </div>

          <div className="mt-5 text-xs leading-5 text-center uppercase tracking-wider">
            <span className="text-theme-500 dark:text-zinc-400">or you can </span>
            <button
              type="button"
              onClick={toggleInput}
              className="inline font-bold underline cursor-pointer text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
            >
              {!showRecoveryInput
                ? 'login using a recovery code'
                : 'login using an authentication code'}
            </button>
          </div>
        </form>
      </div>
    </AuthLayout>
  )
}
