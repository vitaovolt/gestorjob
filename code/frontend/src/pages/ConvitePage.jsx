import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { aceitarConvite, previewConvite } from '../api/auth'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

export default function ConvitePage() {
  const [params] = useSearchParams()
  const token = params.get('token') || ''
  const navigate = useNavigate()
  const { aplicarSessao } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [preview, setPreview] = useState(null)
  const [erro, setErro] = useState('')
  const [name, setName] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!token) {
      setErro('Link de convite incompleto.')
      return undefined
    }
    previewConvite(token)
      .then((payload) => {
        setPreview(payload.data)
        setName(payload.data.name || '')
      })
      .catch((err) => {
        setErro(err.response?.data?.message || 'Convite inválido ou expirado.')
      })
    return undefined
  }, [token])

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!name.trim() || password.length < 8) {
      setErro('Informe o nome e uma senha com pelo menos 8 caracteres.')
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
      const payload = await aceitarConvite(token, {
        name: name.trim(),
        password,
        password_confirmation: passwordConfirmation,
      })
      aplicarSessao(payload.data.token, payload.data.user)
      showToast('Conta ativada')
      navigate(payload.data.user?.wizard_pendente ? '/wizard' : '/', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.errors?.token?.[0] ||
        err.response?.data?.message ||
        'Não foi possível ativar a conta.'
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
      <p className="mt-2 text-[var(--muted)]">
        {preview?.empresa ? `Convite para ${preview.empresa}` : 'Ativar conta'}
      </p>

      <form onSubmit={onSubmit} className="mt-8 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5">
        <label className="block text-sm font-bold text-[var(--moss)]">
          Seu nome
          <input value={name} onChange={(e) => setName(e.target.value)} className={campo} required data-testid="convite-nome" />
        </label>
        {preview?.email ? <p className="mt-2 mb-0 text-sm text-[var(--muted)]">{preview.email}</p> : null}
        <label className="mt-4 block text-sm font-bold text-[var(--moss)]">
          Nova senha
          <input
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className={campo}
            required
            minLength={8}
            data-testid="convite-senha"
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
            data-testid="convite-senha-confirma"
          />
        </label>
        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}
        <button
          type="submit"
          disabled={submitting || !preview}
          className="mt-5 w-full rounded-lg bg-[var(--orange)] px-4 py-3 text-sm font-extrabold text-white disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Ativar conta e continuar'}
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
