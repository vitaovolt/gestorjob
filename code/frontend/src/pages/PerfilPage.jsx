import { useRef, useState } from 'react'
import { alterarSenha } from '../api/auth'
import AppShell from '../components/layout/AppShell.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

export default function PerfilPage() {
  const { user } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [senhaAtual, setSenhaAtual] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [erro, setErro] = useState('')

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (password.length < 8) {
      setErro('A nova senha precisa ter pelo menos 8 caracteres.')
      return
    }
    if (password !== passwordConfirmation) {
      setErro('A confirmação não confere com a nova senha.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')
    try {
      await alterarSenha(senhaAtual, password, passwordConfirmation)
      showToast('Senha atualizada')
      setSenhaAtual('')
      setPassword('')
      setPasswordConfirmation('')
      submittingRef.current = false
      setSubmitting(false)
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.senha_atual?.[0] ||
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        'Não foi possível atualizar a senha.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <AppShell title="Minha conta">
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        {user?.name} · {user?.email}
      </p>
      <form onSubmit={onSubmit} className="max-w-lg rounded-[12px] border border-[var(--line)] bg-white p-5">
        <h2 className="mt-0 mb-4 text-base font-extrabold text-[var(--moss)]">Alterar senha</h2>
        <label className="block text-sm font-bold text-[var(--moss)]">
          Senha atual
          <input
            type="password"
            autoComplete="current-password"
            value={senhaAtual}
            onChange={(e) => setSenhaAtual(e.target.value)}
            className={campo}
            required
            data-testid="perfil-senha-atual"
          />
        </label>
        <label className="mt-3 block text-sm font-bold text-[var(--moss)]">
          Nova senha
          <input
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className={campo}
            required
            minLength={8}
            data-testid="perfil-senha-nova"
          />
        </label>
        <label className="mt-3 block text-sm font-bold text-[var(--moss)]">
          Confirmar nova senha
          <input
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
            className={campo}
            required
            minLength={8}
            data-testid="perfil-senha-confirma"
          />
        </label>
        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}
        <button
          type="submit"
          disabled={submitting}
          className="mt-5 rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Salvar senha'}
        </button>
      </form>
    </AppShell>
  )
}
