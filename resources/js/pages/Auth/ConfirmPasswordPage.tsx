import { Head, useForm } from '@inertiajs/react'
import { type FormEvent } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

export default function ConfirmPasswordPage() {
  const { data, setData, post, processing, errors } = useForm({
    password: '',
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    post(route('password.confirm.store'))
  }

  return (
    <AuthLayout title="Confirm password">
      <div className="flex flex-col gap-6">
        {/* Header */}
        <div className="text-center">
          <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
            Confirm password
          </h2>
          <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            This is a secure area of the application. Please confirm your password before continuing.
          </p>
        </div>

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Password */}
          <div className="grid gap-2">
            <label
              htmlFor="password"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              Password
            </label>
            <input
              id="password"
              type="password"
              value={data.password}
              onChange={(e) => setData('password', e.target.value)}
              required
              autoComplete="current-password"
              placeholder="Password"
              className="input-technical"
            />
            {errors.password && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.password}</p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
            data-test="confirm-password-button"
          >
            {processing ? 'CONFIRMING...' : 'CONFIRM'}
          </button>
        </form>
      </div>
    </AuthLayout>
  )
}
