import { useEffect, useRef, useState } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import { getEmpresaPlataforma, reenviarConvite, updateEmpresa } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useToast } from '../context/ToastContext'

const PLANO = { starter: 'Starter', pro: 'Pro', enterprise: 'Enterprise' }

export default function EmpresaDetalhePage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const location = useLocation()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [empresa, setEmpresa] = useState(null)
  const [conviteUrl, setConviteUrl] = useState(location.state?.conviteUrl || '')
  const [form, setForm] = useState({ nome: '', plano: 'starter', limite_usuarios: '5', status: 'ativo' })
  const [submitting, setSubmitting] = useState(false)
  const [reenviando, setReenviando] = useState(false)
  const [erro, setErro] = useState('')

  useEffect(() => {
    getEmpresaPlataforma(id)
      .then((payload) => {
        const e = payload.data
        setEmpresa(e)
        setForm({
          nome: e.nome || '',
          plano: e.plano || 'starter',
          limite_usuarios: String(e.limite_usuarios ?? 5),
          status: e.status || 'ativo',
        })
      })
      .catch(() => {
        showToast('Empresa não encontrada.', 'erro')
        navigate('/empresas', { replace: true })
      })
  }, [id, navigate, showToast])

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    submittingRef.current = true
    setSubmitting(true)
    setErro('')
    try {
      const payload = await updateEmpresa(id, {
        nome: form.nome.trim(),
        plano: form.plano,
        limite_usuarios: Number(form.limite_usuarios),
        status: form.status,
      })
      setEmpresa(payload.data)
      showToast('Empresa atualizada')
      submittingRef.current = false
      setSubmitting(false)
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg = err.response?.data?.message || 'Não foi possível salvar.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  async function onReenviar() {
    if (submittingRef.current) return
    submittingRef.current = true
    setReenviando(true)
    try {
      const payload = await reenviarConvite(id)
      setEmpresa(payload.data)
      setConviteUrl(payload.data.convite_url || '')
      showToast('Convite reenviado')
    } catch (err) {
      const msg = err.response?.data?.message || 'Não foi possível reenviar o convite.'
      showToast(msg, 'erro')
    } finally {
      submittingRef.current = false
      setReenviando(false)
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  if (!empresa) {
    return (
      <AppShell title="Empresa">
        <p className="text-[var(--muted)]">Carregando…</p>
      </AppShell>
    )
  }

  return (
    <AppShell title={empresa.nome}>
      <div className="mb-4 flex flex-wrap gap-3">
        <div className="min-w-[140px] rounded-[12px] border border-[var(--line)] bg-white px-4 py-3">
          <p className="m-0 text-xs font-bold uppercase text-[var(--muted)]">Plano</p>
          <p className="mt-1 mb-0 font-extrabold text-[var(--moss)]">{PLANO[empresa.plano] || empresa.plano}</p>
        </div>
        <div className="min-w-[140px] rounded-[12px] border border-[var(--line)] bg-white px-4 py-3">
          <p className="m-0 text-xs font-bold uppercase text-[var(--muted)]">Assentos</p>
          <p className="mt-1 mb-0 font-extrabold text-[var(--moss)]">
            {empresa.usuarios_count}/{empresa.limite_usuarios}
          </p>
        </div>
        <div className="min-w-[140px] rounded-[12px] border border-[var(--line)] bg-white px-4 py-3">
          <p className="m-0 text-xs font-bold uppercase text-[var(--muted)]">Status</p>
          <p className="mt-1 mb-0 font-extrabold text-[var(--moss)]">{empresa.status}</p>
        </div>
      </div>

      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5">
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)]">
            Nome
            <input value={form.nome} onChange={(e) => setForm((f) => ({ ...f, nome: e.target.value }))} className={campo} data-testid="empresa-nome" />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Plano
            <select value={form.plano} onChange={(e) => setForm((f) => ({ ...f, plano: e.target.value }))} className={campo} data-testid="empresa-plano">
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
              onChange={(e) => setForm((f) => ({ ...f, limite_usuarios: e.target.value }))}
              className={campo}
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Status
            <select value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))} className={campo} data-testid="empresa-status">
              <option value="ativo">Ativo</option>
              <option value="trial">Trial</option>
              <option value="suspenso">Suspenso</option>
            </select>
          </label>
        </div>
        <p className="mt-3 mb-0 text-sm text-[var(--muted)]">
          Admin: <strong>{empresa.admin?.name}</strong> · {empresa.admin?.email}
          {empresa.admin?.convite_pendente ? ' · convite pendente' : ''}
        </p>
        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}
        <div className="mt-5 flex flex-wrap items-center gap-2">
          <button type="submit" disabled={submitting} className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70">
            {submitting ? 'Processando…' : 'Salvar alterações'}
          </button>
          <Link to="/empresas" className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]">
            Voltar
          </Link>
          {empresa.admin?.convite_pendente ? (
            <button
              type="button"
              onClick={onReenviar}
              disabled={reenviando}
              className="ml-auto rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)] disabled:opacity-70"
            >
              {reenviando ? 'Processando…' : 'Reenviar convite'}
            </button>
          ) : null}
        </div>
      </form>

      {conviteUrl ? (
        <p className="mt-4 max-w-3xl text-sm">
          Link do convite:{' '}
          <a href={conviteUrl} data-testid="convite-url" className="font-bold text-[var(--orange)] break-all">
            {conviteUrl}
          </a>
        </p>
      ) : null}
    </AppShell>
  )
}
