import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { type FormEvent, useState } from 'react'
import AppLayout from '@/layouts/AppLayout'
import SettingsLayout from '@/layouts/SettingsLayout'

interface ProfilePageProps {
  user: {
    name: string
    email: string
    has_verified_email: boolean
  }
  status: string | null
}

export default function ProfilePage({ user, status }: ProfilePageProps) {
  const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
    name: user.name,
    email: user.email,
  })

  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [deletePassword, setDeletePassword] = useState('')
  const [deleteError, setDeleteError] = useState('')

  function submitProfile(e: FormEvent) {
    e.preventDefault()
    patch(route('user-profile-information.update'), {
      preserveScroll: true,
    })
  }

  function handleResendVerification(e: FormEvent) {
    e.preventDefault()
    ;(window as unknown as { location: { href: string } }).location.href = route('verification.send')
  }

  function handleDeleteUser(e: FormEvent) {
    e.preventDefault()
    setDeleteError('')

    const form = document.createElement('form')
    form.method = 'POST'
    form.action = route('current-user.destroy')
    const csrfInput = document.createElement('input')
    csrfInput.type = 'hidden'
    csrfInput.name = '_token'
    const meta = document.querySelector('meta[name="csrf-token"]')
    csrfInput.value = meta ? (meta as HTMLMetaElement).content : ''
    form.appendChild(csrfInput)

    const passwordInput = document.createElement('input')
    passwordInput.type = 'hidden'
    passwordInput.name = 'password'
    passwordInput.value = deletePassword
    form.appendChild(passwordInput)

    const methodInput = document.createElement('input')
    methodInput.type = 'hidden'
    methodInput.name = '_method'
    methodInput.value = 'DELETE'
    form.appendChild(methodInput)

    document.body.appendChild(form)
    form.submit()
  }

  return (
    <AppLayout title="Profile Settings">
      <SettingsLayout
        heading="Profile"
        subheading="Update your name and email address"
      >
        <form onSubmit={submitProfile} className="my-6 w-full space-y-6">
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
              Email
            </label>
            <input
              id="email"
              type="email"
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              required
              autoComplete="email"
              className="input-technical"
            />
            {errors.email && (
              <p className="text-xs text-red-600 dark:text-red-400">{errors.email}</p>
            )}

            {!user.has_verified_email && (
              <div className="mt-4">
                <p className="text-sm text-zinc-600 dark:text-zinc-400">
                  Your email address is unverified.{' '}
                  <button
                    onClick={handleResendVerification}
                    className="text-sm text-theme-500 dark:text-zinc-300 hover:text-theme-700 dark:hover:text-zinc-100 underline cursor-pointer"
                  >
                    Click here to re-send the verification email.
                  </button>
                </p>

                {status === 'verification-link-sent' && (
                  <p className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                    A new verification link has been sent to your email address.
                  </p>
                )}
              </div>
            )}
          </div>

          {/* Save button */}
          <div className="flex items-center gap-4">
            <button
              type="submit"
              disabled={processing}
              className="btn-primary-technical disabled:opacity-75 disabled:cursor-not-allowed"
              data-test="update-profile-button"
            >
              Save
            </button>

            {recentlySuccessful && (
              <span className="text-sm text-green-600 dark:text-green-400">Saved.</span>
            )}
          </div>
        </form>

        {/* Delete Account Section */}
        <div className="mt-10 space-y-6 border-t-2 border-red-300 dark:border-red-800 pt-6">
          <div className="relative mb-5">
            <h3 className="text-sm font-bold text-red-700 dark:text-red-400 uppercase tracking-widest">
              Delete account
            </h3>
            <p className="text-xs text-red-600 dark:text-red-500 uppercase tracking-wider mt-1">
              Delete your account and all of its resources
            </p>
          </div>

          <button
            onClick={() => setShowDeleteModal(true)}
            className="btn-technical border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
            data-test="delete-user-button"
          >
            Delete account
          </button>

          {/* Delete confirmation modal */}
          {showDeleteModal && (
            <div className="fixed inset-0 z-50 flex items-center justify-center">
              <div
                className="absolute inset-0 bg-black/50"
                onClick={() => { setShowDeleteModal(false); setDeleteError(''); setDeletePassword('') }}
              />
              <div className="relative w-full max-w-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <div className="space-y-4">
                  <h3 className="text-lg font-semibold">Are you sure you want to delete your account?</h3>
                  <p className="text-sm text-zinc-600 dark:text-zinc-400">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                    Please enter your password to confirm you would like to permanently delete your account.
                  </p>

                  <div className="grid gap-2">
                    <label
                      htmlFor="delete-password"
                      className="text-xs uppercase tracking-wider text-theme-500 dark:text-zinc-400 font-medium"
                    >
                      Password
                    </label>
                    <input
                      id="delete-password"
                      type="password"
                      value={deletePassword}
                      onChange={(e) => setDeletePassword(e.target.value)}
                      className="input-technical"
                      autoFocus
                    />
                    {deleteError && (
                      <p className="text-xs text-red-600 dark:text-red-400">{deleteError}</p>
                    )}
                  </div>

                  <div className="flex justify-end gap-2">
                    <button
                      onClick={() => { setShowDeleteModal(false); setDeleteError(''); setDeletePassword('') }}
                      className="btn-technical"
                    >
                      Cancel
                    </button>
                    <button
                      onClick={handleDeleteUser}
                      className="btn-technical border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                      data-test="confirm-delete-user-button"
                    >
                      Delete account
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </SettingsLayout>
    </AppLayout>
  )
}
