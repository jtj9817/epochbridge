import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEvent } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

interface ForgotPasswordPageProps {
  status: string | null
}

export default function ForgotPasswordPage({ status }: ForgotPasswordPageProps) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    post(route('password.email'))
  }

  return (
    <AuthLayout title="Forgot Password">
      <div className="flex flex-col gap-6">
        {/* Header */}
        <div className="text-center">
          <h2 className="sd-h2 text-center text-[color:var(--sd-ink)]">
            Forgot password
          </h2>
          <p className="sd-small mt-2 text-center text-[color:var(--sd-ink-soft)]">
            Enter your email to receive a password reset link
          </p>
        </div>

        {/* Status */}
        {status && (
          <div
            className="sd-panel sd-panel--thin p-3 text-center"
            style={{ borderImageSource: 'var(--sd-frame-panel-green)' }}
          >
            <span className="sd-small text-[color:var(--sd-green-dark)]">
              {status}
            </span>
          </div>
        )}

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Email */}
          <div className="grid gap-2">
            <label
              htmlFor="email"
              className="sd-label text-[color:var(--sd-ink-soft)]"
            >
              Email Address
            </label>
            <input
              id="email"
              type="email"
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              required
              autoFocus
              placeholder="email@example.com"
              className="sd-input"
            />
            {errors.email && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.email}</p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="sd-btn sd-btn--primary w-full"
            data-test="email-password-reset-link-button"
          >
            {processing ? 'SENDING...' : 'EMAIL PASSWORD RESET LINK'}
          </button>
        </form>

        {/* Login Link */}
        <div className="sd-small text-center text-[color:var(--sd-ink-soft)]">
          <span>Or, return to </span>
          <Link
            href={route('login')}
            className="font-bold text-[color:var(--sd-gold-text)]"
          >
            log in
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}
