import { Head, useForm } from '@inertiajs/react'
import { type FormEvent, useState } from 'react'
import AppLayout from '@/layouts/AppLayout'
import SettingsLayout from '@/layouts/SettingsLayout'

export default function PasswordPage() {
  const { data, setData, patch, processing, errors, recentlySuccessful, reset } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  })

  const [showCurrentPassword, setShowCurrentPassword] = useState(false)
  const [showNewPassword, setShowNewPassword] = useState(false)

  function submit(e: FormEvent) {
    e.preventDefault()
    patch(route('user-password.update'), {
      preserveScroll: true,
      onSuccess: () => {
        reset('current_password', 'password', 'password_confirmation')
      },
    })
  }

  return (
    <AppLayout title="Password Settings">
      <SettingsLayout
        heading="Update password"
        subheading="Ensure your account is using a long, random password to stay secure"
      >
        <form onSubmit={submit} className="mt-6 space-y-6">
          {/* Current password */}
          <div className="grid gap-2">
            <label
              htmlFor="current_password"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              Current password
            </label>
            <div className="relative">
              <input
                id="current_password"
                type={showCurrentPassword ? 'text' : 'password'}
                value={data.current_password}
                onChange={(e) => setData('current_password', e.target.value)}
                required
                autoComplete="current-password"
                className="input-technical pr-10"
              />
              <button
                type="button"
                onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200"
              >
                {showCurrentPassword ? (
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
            {errors.current_password && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.current_password}</p>
            )}
          </div>

          {/* New password */}
          <div className="grid gap-2">
            <label
              htmlFor="password"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              New password
            </label>
            <div className="relative">
              <input
                id="password"
                type={showNewPassword ? 'text' : 'password'}
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                required
                autoComplete="new-password"
                className="input-technical pr-10"
              />
              <button
                type="button"
                onClick={() => setShowNewPassword(!showNewPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200"
              >
                {showNewPassword ? (
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

          {/* Confirm password */}
          <div className="grid gap-2">
            <label
              htmlFor="password_confirmation"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              Confirm password
            </label>
            <input
              id="password_confirmation"
              type="password"
              value={data.password_confirmation}
              onChange={(e) => setData('password_confirmation', e.target.value)}
              required
              autoComplete="new-password"
              className="input-technical"
            />
          </div>

          {/* Save button */}
          <div className="flex items-center gap-4">
            <button
              type="submit"
              disabled={processing}
              className="btn-primary-technical disabled:opacity-75 disabled:cursor-not-allowed"
              data-test="update-password-button"
            >
              Save
            </button>

            {recentlySuccessful && (
              <span className="text-sm text-green-600 dark:text-green-400">Saved.</span>
            )}
          </div>
        </form>
      </SettingsLayout>
    </AppLayout>
  )
}
