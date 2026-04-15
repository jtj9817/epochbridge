import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEvent } from 'react'
import AuthLayout from '@/layouts/AuthLayout'

interface VerifyEmailPageProps {
  status: string | null
}

export default function VerifyEmailPage({ status }: VerifyEmailPageProps) {
  const { post, processing } = useForm({})

  function sendVerification(e: FormEvent) {
    e.preventDefault()
    post(route('verification.send'))
  }

  function logout(e: FormEvent) {
    e.preventDefault()
    post(route('logout'))
  }

  return (
    <AuthLayout title="Email Verification">
      <div className="mt-4 flex flex-col gap-6">
        <p className="text-center text-sm text-theme-700 dark:text-zinc-300">
          Please verify your email address by clicking on the link we just emailed to you.
        </p>

        {status === 'verification-link-sent' && (
          <p className="text-center font-medium text-green-600 dark:text-green-400">
            A new verification link has been sent to the email address you provided during registration.
          </p>
        )}

        <div className="flex flex-col items-center justify-between space-y-3">
          <button
            onClick={sendVerification}
            disabled={processing}
            className="btn-primary-technical w-full disabled:opacity-75 disabled:cursor-not-allowed"
          >
            {processing ? 'SENDING...' : 'RESEND VERIFICATION EMAIL'}
          </button>

          <Link
            href="#"
            onClick={logout}
            className="text-sm cursor-pointer text-theme-500 dark:text-zinc-400 hover:text-theme-700 dark:hover:text-zinc-200 uppercase tracking-wider"
            data-test="logout-button"
          >
            Log out
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}
