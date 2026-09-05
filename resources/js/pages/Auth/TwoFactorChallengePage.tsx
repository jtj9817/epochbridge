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
            <h2 className="sd-h2 text-center text-[color:var(--sd-ink)]">
              Authentication Code
            </h2>
            <p className="sd-small mt-2 text-center text-[color:var(--sd-ink-soft)]">
              Enter the authentication code provided by your authenticator application.
            </p>
          </div>
        ) : (
          <div className="text-center">
            <h2 className="sd-h2 text-center text-[color:var(--sd-ink)]">
              Recovery Code
            </h2>
            <p className="sd-small mt-2 text-center text-[color:var(--sd-ink-soft)]">
              Please confirm access to your account by entering one of your emergency recovery codes.
            </p>
          </div>
        )}

        <form onSubmit={submit}>
          <div className="space-y-5 text-center">
            {!showRecoveryInput ? (
              <div>
                <div className="my-5 flex items-center justify-center gap-2">
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
                      className="sd-input sd-data-md text-center"
                      style={{ width: '3rem' }}
                      autoComplete="one-time-code"
                    />
                  ))}
                </div>
                {errors.code && (
                  <p className="sd-small text-[color:var(--sd-red)]">{errors.code}</p>
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
                    className="sd-input sd-data-md text-center"
                    placeholder="Recovery code"
                  />
                </div>
                {errors.recovery_code && (
                  <p className="sd-small text-[color:var(--sd-red)]">{errors.recovery_code}</p>
                )}
              </div>
            )}

            <button
              type="submit"
              disabled={processing}
              className="sd-btn sd-btn--primary w-full"
            >
              {processing ? 'VERIFYING...' : 'CONTINUE'}
            </button>
          </div>

          <div className="mt-5 text-center">
            <span className="sd-small text-[color:var(--sd-ink-soft)]">or you can </span>
            <button
              type="button"
              onClick={toggleInput}
              className="sd-small inline font-bold underline text-[color:var(--sd-gold-text)]"
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
