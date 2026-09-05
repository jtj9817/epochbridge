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
          <h2 className="sd-h2 text-center text-[color:var(--sd-ink)]">
            Confirm password
          </h2>
          <p className="sd-small mt-2 text-center text-[color:var(--sd-ink-soft)]">
            This is a secure area of the application. Please confirm your password before continuing.
          </p>
        </div>

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Password */}
          <div className="grid gap-2">
            <label
              htmlFor="password"
              className="sd-label text-[color:var(--sd-ink-soft)]"
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
              className="sd-input"
            />
            {errors.password && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.password}</p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="sd-btn sd-btn--primary w-full"
            data-test="confirm-password-button"
          >
            {processing ? 'CONFIRMING...' : 'CONFIRM'}
          </button>
        </form>
      </div>
    </AuthLayout>
  )
}
