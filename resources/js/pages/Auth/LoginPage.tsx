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
          <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
            Log in to your account
          </h2>
          <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            Enter your email and password below to log in
          </p>
        </div>

        {/* Session Status */}
        {status && (
          <div className="p-3 text-xs text-center font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 uppercase tracking-wider">
            {status}
          </div>
        )}

        {/* OAuth Errors */}
        {oauthError && (
          <div className="p-3 text-xs text-red-800 dark:text-red-200 bg-red-100 dark:bg-red-900/30 border-2 border-red-300 dark:border-red-800 uppercase tracking-wider">
            <strong>Authentication Error:</strong> {oauthError}
          </div>
        )}

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Email */}
          <div className="grid gap-2">
            <label
              htmlFor="email"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
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
              className="input-technical"
            />
            {errors.email && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.email}</p>
            )}
          </div>

          {/* Password */}
          <div className="grid gap-2">
            <div className="flex items-center justify-between">
              <label
                htmlFor="password"
                className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
              >
                Password
              </label>
              {canResetPassword && (
                <Link
                  href={route('password.request')}
                  className="text-xs text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200 uppercase tracking-wider"
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
                className="input-technical pr-10"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200"
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
              <p className="text-xs text-red-600 dark:text-red-400">{errors.password}</p>
            )}
          </div>

          {/* Remember Me */}
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={data.remember}
              onChange={(e) => setData('remember', e.target.checked)}
              className="w-4 h-4 border-2 border-theme-300 dark:border-zinc-600 accent-theme-500"
            />
            <span className="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
              Remember me
            </span>
          </label>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
            data-test="login-button"
          >
            {processing ? 'SIGNING IN...' : 'LOG IN'}
          </button>
        </form>

        {/* Social Login Divider */}
        <div className="relative flex items-center justify-center">
          <div className="flex-grow border-t border-theme-300 dark:border-zinc-700" />
          <span className="flex-shrink mx-4 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            Or continue with
          </span>
          <div className="flex-grow border-t border-theme-300 dark:border-zinc-700" />
        </div>

        {/* Google Sign-In Button */}
        <a
          href={route('social.redirect', { provider: 'google' })}
          className="flex items-center justify-center px-4 py-2 bg-white dark:bg-zinc-800 border-2 border-theme-300 dark:border-zinc-600 font-bold text-xs text-theme-700 dark:text-zinc-300 hover:bg-theme-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-theme-500 uppercase tracking-wider transition-colors"
        >
          <svg className="w-5 h-5 mr-2" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
          </svg>
          Sign in with Google
        </a>

        {/* Register Link */}
        {canRegister && (
          <div className="text-xs text-center text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            <span>Don&apos;t have an account? </span>
            <Link
              href={route('register')}
              className="font-bold text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
            >
              Sign up
            </Link>
          </div>
        )}
      </div>
    </AuthLayout>
  )
}
