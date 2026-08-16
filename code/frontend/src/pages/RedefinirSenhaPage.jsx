import { useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { redefinirSenha } from '../api/auth'
import { useToast } from '../context/ToastContext'

export default function RedefinirSenhaPage() {
  const [params] = useSearchParams()
  const token = params.get('token') || ''
  const navigate = useNavigate()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [erro, setErro] = useState(token ? '' : 'Link incompleto. Solicite um novo em Recuperar senha.')
  const [submitting, setSubmitting] = useState(false)

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current || !token) return
    if (password.length < 8) {
      setErro('A senha precisa ter pelo menos 8 caracteres.')
      return
    }
    if (password !== passwordConfirmation) {
      setErro('A confirmação não confere com a senha.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    try {
      await redefinirSenha(token, password, passwordConfirmation)
      showToast('Senha redefinida')
      navigate('/login', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.token?.[0] ||
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        'Não foi possível redefinir a senha.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2.5 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-12">
      <h1 className="mt-2 text-4xl font-extrabold tracking-tight text-[var(--moss)]">
        Gestor<span className="text-[var(--orange)]">Job</span>
      </h1>
      <p className="mt-2 text-[var(--muted)]">Nova senha</p>

      <form
        onSubmit={onSubmit}
        className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5"
        data-testid="form-redefinir"
      >
        <label className="block text-sm font-bold text-[var(--moss)]">
          Nova senha
          <input
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className={campo}
            required
            minLength={8}
            disabled={!token}
            data-testid="redefinir-senha"
          />
        </label>
        <label className="mt-4 block text-sm font-bold text-[var(--moss)]">
          Confirmar senha
          <input
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            className={campo}
            required
            minLength={8}
            disabled={!token}
            data-testid="redefinir-senha-confirma"
          />
        </label>
        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}
        <button
          type="submit"
          disabled={submitting || !token}
          data-testid="redefinir-enviar"
          className="mt-5 w-full rounded-lg bg-[var(--orange)] px-4 py-3 text-sm font-extrabold text-white disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Salvar nova senha'}
        </button>
        <p className="mt-3 mb-0 text-center">
          <Link to="/login" className="text-sm font-bold text-[var(--moss)]">
            Voltar ao login
          </Link>
        </p>
      </form>
    </main>
  )
}
