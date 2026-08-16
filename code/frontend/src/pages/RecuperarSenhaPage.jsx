import { useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { solicitarRecuperacaoSenha } from '../api/auth'
import { emailValido, normalizeEmail } from '../utils/masks'

export default function RecuperarSenhaPage() {
  const navigate = useNavigate()
  const submittingRef = useRef(false)
  const [email, setEmail] = useState('')
  const [erro, setErro] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return

    const emailTrim = normalizeEmail(email)
    if (!emailTrim) {
      setErro('Informe o e-mail cadastrado.')
      return
    }
    if (!emailValido(emailTrim)) {
      setErro('Informe um e-mail válido.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    try {
      const payload = await solicitarRecuperacaoSenha(emailTrim)
      navigate('/recuperar-ok', {
        replace: true,
        state: { resetUrl: payload.data?.reset_url || null },
      })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.message ||
        'Não foi possível enviar o link.'
      setErro(msg)
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
      <p className="mt-2 text-[var(--muted)]">Recuperar senha</p>

      <form
        onSubmit={onSubmit}
        className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5 shadow-[0_10px_30px_rgba(26,34,32,0.08)]"
        data-testid="form-recuperar"
      >
        <label className="block text-sm font-bold text-[var(--moss)]">
          E-mail cadastrado
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
            data-testid="recuperar-email"
          />
        </label>

        {erro ? (
          <p className="mt-3 text-sm font-semibold text-[#b42318]" role="alert">
            {erro}
          </p>
        ) : null}

        <button
          type="submit"
          disabled={submitting}
          data-testid="recuperar-enviar"
          className="mt-5 w-full rounded-lg bg-[var(--orange)] px-4 py-3 text-sm font-extrabold text-white hover:brightness-110 disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Enviar link'}
        </button>

        <p className="mt-3 mb-0 text-center">
          <Link to="/login" className="text-sm font-bold text-[var(--moss)]">
            Voltar
          </Link>
        </p>
      </form>
    </main>
  )
}
