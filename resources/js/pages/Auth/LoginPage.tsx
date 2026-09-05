import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEvent, useState } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

interface LoginPageProps {
  canResetPassword: boolean
  canRegister: boolean
  status: string | null
  oauthError: string | null
}

export default function LoginPage({
  canResetPassword,
  canRegister,
  status,
  oauthError,
}: LoginPageProps) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false as boolean,
  })

  const [showPassword, setShowPassword] = useState(false)

  function submit(e: FormEvent) {
    e.preventDefault()
    post(route('login.store'))
  }

  return (
    <AuthLayout title="Log in">
      <div className="flex flex-col gap-6">
        {/* Header */}
        <div className="text-center">
          <h2 className="sd-h2 text-center text-[color:var(--sd-ink)]">
            Log in to your account
          </h2>
          <p className="sd-small mt-2 text-center text-[color:var(--sd-ink-soft)]">
            Enter your email and password below to log in
          </p>
        </div>

        {/* Session Status */}
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

        {/* OAuth Errors */}
        {oauthError && (
          <div
            className="sd-panel sd-panel--thin p-3"
            style={{ borderImageSource: 'var(--sd-frame-panel-red)' }}
          >
            <p className="sd-small text-[color:var(--sd-red)]">
              <strong>Authentication Error:</strong> {oauthError}
            </p>
          </div>
        )}

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Email */}
          <div className="grid gap-2">
            <label
              htmlFor="email"
              className="sd-label text-[color:var(--sd-ink-soft)]"
            >
              Email address
            </label>
            <input
              id="email"
              type="email"
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              required
              autoFocus
              autoComplete="email"
              placeholder="email@example.com"
              className="sd-input"
            />
            {errors.email && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.email}</p>
            )}
          </div>

          {/* Password */}
          <div className="grid gap-2">
            <div className="flex items-center justify-between">
              <label
                htmlFor="password"
                className="sd-label text-[color:var(--sd-ink-soft)]"
              >
                Password
              </label>
              {canResetPassword && (
                <Link
                  href={route('password.request')}
                  className="sd-small underline text-[color:var(--sd-gold-text)]"
                >
                  Forgot your password?
                </Link>
              )}
            </div>
            <div className="relative">
              <input
                id="password"
                type={showPassword ? 'text' : 'password'}
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                required
                autoComplete="current-password"
                placeholder="Password"
                className="sd-input"
                style={{ paddingRight: '2.5rem' }}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[color:var(--sd-ink-soft)]"
              >
                {showPassword ? (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                ) : (
                  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                )}
              </button>
            </div>
            {errors.password && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.password}</p>
            )}
          </div>

          {/* Remember Me */}
          <label className="flex cursor-pointer items-center gap-2">
            <input
              type="checkbox"
              checked={data.remember}
              onChange={(e) => setData('remember', e.target.checked)}
              className="h-4 w-4 accent-[color:var(--sd-green)]"
            />
            <span className="sd-label text-[color:var(--sd-ink-soft)]">
              Remember me
            </span>
          </label>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="sd-btn sd-btn--primary w-full"
            data-test="login-button"
          >
            {processing ? 'SIGNING IN...' : 'LOG IN'}
          </button>
        </form>

        {/* Social Login Divider */}
        <div className="relative flex items-center justify-center">
          <div className="h-0.5 flex-grow bg-[color:var(--sd-parch-shadow)]" />
          <span className="sd-label mx-4 shrink-0 text-[color:var(--sd-ink-soft)]">
            Or continue with
          </span>
          <div className="h-0.5 flex-grow bg-[color:var(--sd-parch-shadow)]" />
        </div>

        {/* Google Sign-In Button */}
        <a
          href={route('social.redirect', { provider: 'google' })}
          className="sd-btn sd-btn--ghost w-full"
        >
          <img
            src="/images/sd/sparkle.svg"
            width={16}
            height={16}
            className="sd-pixel"
            aria-hidden="true"
          />
          Sign in with Google
        </a>

        {/* Register Link */}
        {canRegister && (
          <div className="sd-small text-center text-[color:var(--sd-ink-soft)]">
            <span>Don&apos;t have an account? </span>
            <Link
              href={route('register')}
              className="font-bold text-[color:var(--sd-gold-text)]"
            >
              Sign up
            </Link>
          </div>
        )}
      </div>
    </AuthLayout>
  )
}
