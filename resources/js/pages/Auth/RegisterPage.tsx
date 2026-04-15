import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEvent, useState } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

export default function RegisterPage() {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const [showPassword, setShowPassword] = useState(false)

  function submit(e: FormEvent) {
    e.preventDefault()
    post(route('register.store'))
  }

  return (
    <AuthLayout title="Register">
      <div className="flex flex-col gap-6">
        {/* Header */}
        <div className="text-center">
          <h2 className="text-lg font-bold uppercase tracking-wider text-theme-900 dark:text-theme-50">
            Create an account
          </h2>
          <p className="mt-1 text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
            Enter your details below to create your account
          </p>
        </div>

        <form onSubmit={submit} className="flex flex-col gap-6">
          {/* Name */}
          <div className="grid gap-2">
            <label
              htmlFor="name"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              Name
            </label>
            <input
              id="name"
              type="text"
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              required
              autoFocus
              autoComplete="name"
              placeholder="Full name"
              className="input-technical"
            />
            {errors.name && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.name}</p>
            )}
          </div>

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
            <label
              htmlFor="password"
              className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
            >
              Password
            </label>
            <div className="relative">
              <input
                id="password"
                type={showPassword ? 'text' : 'password'}
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                required
                autoComplete="new-password"
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

          {/* Confirm Password */}
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
              placeholder="Confirm password"
              className="input-technical"
            />
            {errors.password_confirmation && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.password_confirmation}</p>
            )}
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={processing}
            className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
            data-test="register-user-button"
          >
            {processing ? 'CREATING ACCOUNT...' : 'CREATE ACCOUNT'}
          </button>
        </form>

        {/* Login Link */}
        <div className="text-xs text-center text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
          <span>Already have an account? </span>
          <Link
            href={route('login')}
            className="font-bold text-theme-700 dark:text-zinc-300 hover:text-theme-900 dark:hover:text-white"
          >
            Log in
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}
