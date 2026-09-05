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
        <p className="sd-small text-center text-[color:var(--sd-ink)]">
          Please verify your email address by clicking on the link we just emailed to you.
        </p>

        {status === 'verification-link-sent' && (
          <div
            className="sd-panel sd-panel--thin p-3 text-center"
            style={{ borderImageSource: 'var(--sd-frame-green)' }}
          >
            <span className="sd-small text-[color:var(--sd-green-dark)]">
              A new verification link has been sent to the email address you provided during registration.
            </span>
          </div>
        )}

        <div className="flex flex-col items-center justify-between space-y-3">
          <button
            onClick={sendVerification}
            disabled={processing}
            className="sd-btn sd-btn--primary w-full"
          >
            {processing ? 'SENDING...' : 'RESEND VERIFICATION EMAIL'}
          </button>

          <Link
            href="#"
            onClick={logout}
            className="sd-small cursor-pointer underline text-[color:var(--sd-ink-soft)]"
            data-test="logout-button"
          >
            Log out
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}
