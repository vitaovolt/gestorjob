import { useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { createEmpresa } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useToast } from '../context/ToastContext'
import { emailValido, normalizeEmail } from '../utils/masks'

const VAZIO = {
  nome: '',
  plano: 'pro',
  limite_usuarios: '10',
  admin_nome: '',
  admin_email: '',
}

export default function EmpresaFormPage() {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(VAZIO)
  const [submitting, setSubmitting] = useState(false)
  const [erro, setErro] = useState('')

  function setCampo(campo, valor) {
    setForm((atual) => ({ ...atual, [campo]: valor }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.nome.trim()) {
      setErro('Informe o nome da empresa.')
      return
    }
    const email = normalizeEmail(form.admin_email)
    if (!form.admin_nome.trim() || !emailValido(email)) {
      setErro('Informe nome e e-mail válidos do admin.')
      return
    }
    const limite = Number(form.limite_usuarios)
    if (!Number.isInteger(limite) || limite < 1) {
      setErro('Limite de assentos inválido.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    try {
      const payload = await createEmpresa({
        nome: form.nome.trim(),
        plano: form.plano,
        limite_usuarios: limite,
        admin_nome: form.admin_nome.trim(),
        admin_email: email,
      })
      showToast('Empresa criada. Convite enviado.')
      navigate(`/empresas/${payload.data.id}`, {
        replace: true,
        state: { conviteUrl: payload.data.convite_url },
      })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.admin_email?.[0] ||
        err.response?.data?.errors?.nome?.[0] ||
        err.response?.data?.message ||
        'Não foi possível criar a empresa.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <AppShell title="Nova empresa">
      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5">
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)]">
            Nome da empresa
            <input
              value={form.nome}
              onChange={(e) => setCampo('nome', e.target.value)}
              className={campo}
              required
              data-testid="empresa-nome"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Plano
            <select value={form.plano} onChange={(e) => setCampo('plano', e.target.value)} className={campo} data-testid="empresa-plano">
              <option value="starter">Starter</option>
              <option value="pro">Pro</option>
              <option value="enterprise">Enterprise</option>
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Limite de assentos
            <input
              type="number"
              min="1"
              max="500"
              value={form.limite_usuarios}
              onChange={(e) => setCampo('limite_usuarios', e.target.value)}
              className={campo}
              data-testid="empresa-assentos"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Nome do admin
            <input
              value={form.admin_nome}
              onChange={(e) => setCampo('admin_nome', e.target.value)}
              className={campo}
              required
              data-testid="empresa-admin-nome"
            />
          </label>
          <label className="md:col-span-2 block text-sm font-bold text-[var(--moss)]">
            E-mail do admin
            <input
              type="email"
              inputMode="email"
              autoCapitalize="none"
              value={form.admin_email}
              onChange={(e) => setCampo('admin_email', e.target.value)}
              className={campo}
              required
              data-testid="empresa-admin-email"
            />
          </label>
        </div>
        <p className="mt-3 mb-0 text-xs font-medium text-[var(--muted)]">
          O admin recebe um link para definir a senha e depois segue o wizard de onboarding.
        </p>
        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}
        <div className="mt-5 flex flex-wrap items-center gap-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
          >
            {submitting ? 'Processando…' : 'Criar e enviar convite'}
          </button>
          <Link to="/empresas" className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]">
            Cancelar
          </Link>
        </div>
      </form>
    </AppShell>
  )
}
