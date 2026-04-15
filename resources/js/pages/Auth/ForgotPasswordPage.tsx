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
          <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
            Forgot password
          </h2>
          <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            Enter your email to receive a password reset link
          </p>
        </div>

        {/* Status */}
        {status && (
          <div className="p-3 text-xs text-center font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 uppercase tracking-wider">
            {status}
          </div>
        )}

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Email */}
          <div className="grid gap-2">
            <label
              htmlFor="email"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
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
              className="input-technical"
            />
            {errors.email && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.email}</p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
            data-test="email-password-reset-link-button"
          >
            {processing ? 'SENDING...' : 'EMAIL PASSWORD RESET LINK'}
          </button>
        </form>

        {/* Login Link */}
        <div className="text-xs text-center text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
          <span>Or, return to </span>
          <Link
            href={route('login')}
            className="font-bold text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
          >
            log in
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}
