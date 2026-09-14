import { useEffect, useState } from 'react'
import { fetchHealth } from '../api/health'
import LogoGestorJob from '../components/brand/LogoGestorJob.jsx'

export default function BootstrapPage() {
  const [health, setHealth] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let cancelled = false
    fetchHealth()
      .then((data) => {
        if (!cancelled) setHealth(data)
      })
      .catch(() => {
        if (!cancelled) setError('Não foi possível falar com a API. Suba o backend em :8000.')
      })
    return () => {
      cancelled = true
    }
  }, [])

  return (
    <main className="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-5 py-12">
      <LogoGestorJob className="h-16 w-auto max-w-[280px]" />
      <p className="mt-2 text-[var(--muted)]">
        Bootstrap F0 — API Laravel + SPA React. Login e Kanban entram nas próximas fases.
      </p>

      <section className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-4 shadow-[0_10px_30px_rgba(26,34,32,0.08)]">
        <p className="m-0 text-[0.7rem] font-extrabold tracking-wider uppercase text-[var(--muted)]">
          Health da API
        </p>
        {error ? (
          <p className="mt-2 font-semibold text-[#b42318]">{error}</p>
        ) : !health ? (
          <p className="mt-2 text-[var(--muted)]">Consultando /api/v1/health…</p>
        ) : (
          <>
            <p className="mt-2 text-lg font-extrabold text-[var(--moss)]">Bootstrap OK</p>
            <pre className="mt-2 overflow-auto rounded-lg bg-[var(--moss)] p-3 text-xs text-[var(--moss-soft)]">
{JSON.stringify(health, null, 2)}
            </pre>
          </>
        )}
      </section>
    </main>
  )
}
