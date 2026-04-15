import { Head } from '@inertiajs/react'

export default function Test() {
  return (
    <>
      <Head title="React Test" />
      <div className="min-h-screen flex items-center justify-center bg-zinc-100">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-zinc-900">
            React + Inertia Works!
          </h1>
          <p className="mt-4 text-zinc-600">
            Phase 1 setup is complete.
          </p>
        </div>
      </div>
    </>
  )
}
