import { Link, useLocation } from 'react-router-dom'

export default function RecuperarSenhaOkPage() {
  const location = useLocation()
  const resetUrl = location.state?.resetUrl || null

  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12 text-center">
      <h1 className="mt-2 text-4xl font-extrabold tracking-tight text-[var(--moss)]">
        Gestor<span className="text-[var(--orange)]">Job</span>
      </h1>
      <div
        className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-6 shadow-[0_10px_30px_rgba(26,34,32,0.08)]"
        data-testid="recuperar-ok"
      >
        <h2 className="mt-0 mb-2 text-xl font-extrabold text-[var(--moss)]">Link enviado</h2>
        <p className="m-0 text-sm text-[var(--muted)]">
          Confira sua caixa de entrada e defina uma nova senha.
        </p>
        {resetUrl ? (
          <p className="mt-4 mb-0 break-all text-left text-xs text-[var(--moss)]">
            <span className="font-bold">Link local (dev): </span>
            <a href={resetUrl} data-testid="recuperar-reset-url" className="font-semibold underline">
              {resetUrl}
            </a>
          </p>
        ) : null}
        <Link
          to="/login"
          className="mt-6 inline-flex rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-extrabold text-[var(--moss)]"
        >
          Voltar ao login
        </Link>
      </div>
    </main>
  )
}
