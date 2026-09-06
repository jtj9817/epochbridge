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
  const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
    name: user.name,
    email: user.email,
  })

  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [deletePassword, setDeletePassword] = useState('')
  const [deleteError, setDeleteError] = useState('')

  function submitProfile(e: FormEvent) {
    e.preventDefault()
    put(route('user-profile-information.update'), {
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

  function closeDeleteModal() {
    setShowDeleteModal(false)
    setDeleteError('')
    setDeletePassword('')
  }

  return (
    <AppLayout title="Profile Settings">
      <SettingsLayout
        heading="Profile"
        subheading="Update your name and email address"
      >
        <form onSubmit={submitProfile} className="w-full space-y-6">
          {/* Name */}
          <div className="grid gap-2">
            <label
              htmlFor="name"
              className="sd-label text-[color:var(--sd-ink-soft)]"
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
              className="sd-input"
            />
            {errors.name && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.name}</p>
            )}
          </div>

          {/* Email */}
          <div className="grid gap-2">
            <label
              htmlFor="email"
              className="sd-label text-[color:var(--sd-ink-soft)]"
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
              className="sd-input"
            />
            {errors.email && (
              <p className="sd-small text-[color:var(--sd-red)]">{errors.email}</p>
            )}

            {!user.has_verified_email && (
              <div className="mt-4">
                <p className="sd-small text-[color:var(--sd-ink-soft)]">
                  Your email address is unverified.{' '}
                  <button
                    onClick={handleResendVerification}
                    className="sd-small underline text-[color:var(--sd-gold-text)]"
                  >
                    Click here to re-send the verification email.
                  </button>
                </p>

                {status === 'verification-link-sent' && (
                  <p className="sd-small mt-2 text-[color:var(--sd-green-dark)]">
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
              className="sd-btn sd-btn--primary sd-btn--sm"
              data-test="update-profile-button"
            >
              Save
            </button>

            {recentlySuccessful && (
              <span className="sd-small text-[color:var(--sd-green-dark)]">Saved.</span>
            )}
          </div>
        </form>

        {/* Delete Account Section */}
        <div className="mt-10">
          <div
            className="sd-panel sd-panel--thin space-y-6 p-6"
            style={{ borderImageSource: 'var(--sd-frame-panel-red)' }}
          >
            <div className="relative">
              <div className="mb-2 flex items-center gap-2">
                <img
                  src="/images/sd/icon-trash.svg"
                  width={16}
                  height={16}
                  className="sd-pixel"
                  aria-hidden="true"
                />
                <h3 className="sd-h3 text-[color:var(--sd-red)]">
                  Delete account
                </h3>
              </div>
              <p className="sd-small text-[color:var(--sd-ink-soft)]">
                Delete your account and all of its resources
              </p>
            </div>

            <button
              onClick={() => setShowDeleteModal(true)}
              className="sd-btn sd-btn--danger"
              data-test="delete-user-button"
            >
              Delete account
            </button>

            {/* Delete confirmation modal */}
            {showDeleteModal && (
              <div className="fixed inset-0 z-50 flex items-center justify-center">
                <div
                  className="sd-overlay absolute inset-0"
                  onClick={closeDeleteModal}
                />
                <div className="sd-panel sd-pop relative w-full max-w-lg p-6">
                  <div className="sd-panel__title">
                    <div className="flex items-center gap-2">
                      <img
                        src="/images/sd/icon-trash.svg"
                        width={16}
                        height={16}
                        className="sd-pixel"
                        aria-hidden="true"
                      />
                      <h3 className="sd-label text-[color:var(--sd-ink)]">
                        Are you sure you want to delete your account?
                      </h3>
                    </div>
                    <button
                      onClick={closeDeleteModal}
                      aria-label="Close modal"
                      className="sd-btn sd-btn--ghost sd-btn--sm"
                    >
                      <img
                        src="/images/sd/icon-x.svg"
                        width={16}
                        height={16}
                        className="sd-pixel"
                        aria-hidden="true"
                      />
                    </button>
                  </div>

                  <div className="space-y-4 pt-4">
                    <p className="sd-small text-[color:var(--sd-ink-soft)]">
                      Once your account is deleted, all of its resources and data will be permanently deleted.
                      Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <div className="grid gap-2">
                      <label
                        htmlFor="delete-password"
                        className="sd-label text-[color:var(--sd-ink-soft)]"
                      >
                        Password
                      </label>
                      <input
                        id="delete-password"
                        type="password"
                        value={deletePassword}
                        onChange={(e) => setDeletePassword(e.target.value)}
                        className="sd-input"
                        autoFocus
                      />
                      {deleteError && (
                        <p className="sd-small text-[color:var(--sd-red)]">{deleteError}</p>
                      )}
                    </div>

                    <div className="flex justify-end gap-2">
                      <button
                        onClick={closeDeleteModal}
                        className="sd-btn sd-btn--ghost"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={handleDeleteUser}
                        className="sd-btn sd-btn--danger"
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
        </div>
      </SettingsLayout>
    </AppLayout>
  )
}
