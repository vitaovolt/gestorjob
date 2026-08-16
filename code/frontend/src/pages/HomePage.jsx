import { useRef, useState } from 'react'
import { useAuth } from '../context/AuthContext'

export default function HomePage() {
  const { user, logout } = useAuth()
  const submittingRef = useRef(false)
  const [saindo, setSaindo] = useState(false)

  async function onLogout() {
    if (submittingRef.current) return
    submittingRef.current = true
    setSaindo(true)
    try {
      await logout()
    } catch {
      submittingRef.current = false
      setSaindo(false)
    }
  }

  const empresa = user?.empresa?.nome || (user?.papel === 'super_admin' ? 'Plataforma' : '—')

  return (
    <main className="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-5 py-12">
      <p className="m-0 text-xs font-extrabold tracking-[0.14em] uppercase text-[var(--moss)]">
        Organize · Descomplique
      </p>
      <h1 className="mt-2 text-3xl font-extrabold tracking-tight text-[var(--moss)]">
        Olá, {user?.name?.split(' ')[0] || 'você'}
      </h1>
      <p className="mt-2 text-[var(--muted)]">
        {empresa} · {user?.papel}
      </p>

      <section className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-4 shadow-[0_10px_30px_rgba(26,34,32,0.08)]">
        <p className="m-0 text-[0.7rem] font-extrabold tracking-wider uppercase text-[var(--muted)]">
          Sessão
        </p>
        <p className="mt-2 font-semibold text-[var(--ink)]">{user?.email}</p>
        <p className="mt-2 text-sm text-[var(--muted)]">
          Login OK. O Kanban e o timer entram na próxima fase (F3).
        </p>
        <button
          type="button"
          onClick={onLogout}
          disabled={saindo}
          className="mt-4 rounded-lg border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-extrabold text-[var(--moss)] disabled:opacity-70"
        >
          {saindo ? 'Saindo…' : 'Sair'}
        </button>
      </section>
    </main>
  )
}
