import { useRef, useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { emailValido, normalizeEmail } from '../utils/masks'
import { destinoInicial } from '../utils/format'

export default function LoginPage() {
  const { login, isAuthenticated, loading, user } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const submittingRef = useRef(false)
  const [email, setEmail] = useState('mariana@agenciaeduc.local')
  const [password, setPassword] = useState('password')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  if (!loading && isAuthenticated) {
    return <Navigate to={destinoInicial(user)} replace />
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return

    const emailTrim = normalizeEmail(email)
    if (!emailTrim || !password) {
      setError('Informe e-mail e senha.')
      return
    }
    if (!emailValido(emailTrim)) {
      setError('Informe um e-mail válido.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setError('')

    try {
      const logado = await login(emailTrim, password)
      navigate(destinoInicial(logado, location.state?.from?.pathname), { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.message ||
        'Não foi possível entrar. Verifique e-mail e senha.'
      setError(msg)
    }
  }

  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12">
      <p className="m-0 text-xs font-extrabold tracking-[0.14em] uppercase text-[var(--moss)]">
        Organize · Descomplique
      </p>
      <h1 className="mt-2 text-4xl font-extrabold tracking-tight text-[var(--moss)]">
        Gestor<span className="text-[var(--orange)]">Job</span>
      </h1>
      <p className="mt-2 text-[var(--muted)]">Entre com o e-mail da agência.</p>

      <form
        onSubmit={onSubmit}
        className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5 shadow-[0_10px_30px_rgba(26,34,32,0.08)]"
      >
        <label className="block text-sm font-bold text-[var(--moss)]">
          E-mail
          <input
            type="email"
            inputMode="email"
            autoComplete="username"
            autoCapitalize="none"
            autoCorrect="off"
            spellCheck={false}
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2.5 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]"
            required
          />
        </label>

        <label className="mt-4 block text-sm font-bold text-[var(--moss)]">
          Senha
          <input
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2.5 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]"
            required
          />
        </label>

        {error ? (
          <p className="mt-3 text-sm font-semibold text-[#b42318]" role="alert">
            {error}
          </p>
        ) : null}

        <button
          type="submit"
          disabled={submitting}
          className="mt-5 w-full rounded-lg bg-[var(--orange)] px-4 py-3 text-sm font-extrabold text-white hover:brightness-110 disabled:opacity-70"
        >
          {submitting ? 'Entrando…' : 'Entrar'}
        </button>

        <p className="mt-4 mb-0 flex justify-between gap-3 text-sm">
          <Link to="/recuperar" className="font-bold text-[var(--moss)]" data-testid="link-esqueci-senha">
            Esqueci a senha
          </Link>
        </p>
      </form>
    </main>
  )
}
