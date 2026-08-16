import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  createColaborador,
  deleteColaborador,
  getColaborador,
  updateColaborador,
} from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { temPermissao } from '../utils/format'
import { emailValido, maskMoneyBR, moneyFromNumber, normalizeEmail, parseMoneyBR } from '../utils/masks'

const VAZIO = {
  name: '',
  email: '',
  papel: 'colaborador',
  departamento: '',
  custo_hora: '',
  carga_semanal_horas: '40',
  password: '',
  password_confirmation: '',
}

export default function ColaboradorFormPage() {
  const { id } = useParams()
  const novo = !id
  const navigate = useNavigate()
  const { user } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(VAZIO)
  const [submitting, setSubmitting] = useState(false)
  const [excluindo, setExcluindo] = useState(false)
  const [confirmarExcluir, setConfirmarExcluir] = useState(false)
  const [erro, setErro] = useState('')

  useEffect(() => {
    if (!temPermissao(user, 'cadastrar_equipe')) {
      navigate('/colaboradores', { replace: true })
    }
  }, [user, navigate])

  useEffect(() => {
    if (novo) return undefined
    getColaborador(id)
      .then((payload) => {
        const p = payload.data
        setForm({
          name: p.name || '',
          email: p.email || '',
          papel: p.papel || 'colaborador',
          departamento: p.departamento || '',
          custo_hora: moneyFromNumber(p.custo_hora),
          carga_semanal_horas: p.carga_semanal_horas ?? '40',
          password: '',
          password_confirmation: '',
        })
      })
      .catch(() => {
        showToast('Colaborador não encontrado.', 'erro')
        navigate('/colaboradores', { replace: true })
      })
    return undefined
  }, [id, novo, navigate, showToast])

  function setCampo(campo, valor) {
    setForm((atual) => ({ ...atual, [campo]: valor }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.name.trim()) {
      setErro('Informe o nome.')
      return
    }
    const email = normalizeEmail(form.email)
    if (!emailValido(email)) {
      setErro('Informe um e-mail válido.')
      return
    }
    const custo = form.custo_hora === '' ? null : parseMoneyBR(form.custo_hora)
    if (form.custo_hora !== '' && !Number.isFinite(custo)) {
      setErro('Custo/hora inválido.')
      return
    }
    if (form.password && form.password.length < 8) {
      setErro('A senha precisa ter pelo menos 8 caracteres.')
      return
    }
    if (form.password && form.password !== form.password_confirmation) {
      setErro('A confirmação não confere com a senha.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    const payload = {
      name: form.name.trim(),
      email,
      papel: form.papel,
      departamento: form.departamento.trim() || null,
      custo_hora: custo,
      carga_semanal_horas: form.carga_semanal_horas === '' ? null : Number(form.carga_semanal_horas),
    }
    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    try {
      if (novo) {
        await createColaborador(payload)
        showToast('Colaborador criado')
      } else {
        await updateColaborador(id, payload)
        showToast('Colaborador atualizado')
      }
      navigate('/colaboradores', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.name?.[0] ||
        err.response?.data?.message ||
        'Não foi possível salvar o colaborador.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  async function onExcluir() {
    if (submittingRef.current) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteColaborador(id)
      showToast('Colaborador removido')
      navigate('/colaboradores', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setExcluindo(false)
      setConfirmarExcluir(false)
      const msg = err.response?.data?.message || 'Não foi possível excluir o colaborador.'
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <AppShell title={novo ? 'Novo colaborador' : 'Editar colaborador'}>
      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5">
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)]">
            Nome
            <input
              value={form.name}
              onChange={(e) => setCampo('name', e.target.value)}
              className={campo}
              required
              data-testid="colaborador-nome"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            E-mail
            <input
              type="email"
              inputMode="email"
              autoCapitalize="none"
              autoCorrect="off"
              spellCheck={false}
              value={form.email}
              onChange={(e) => setCampo('email', e.target.value)}
              className={campo}
              required
              data-testid="colaborador-email"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Papel
            <select
              value={form.papel}
              onChange={(e) => setCampo('papel', e.target.value)}
              className={campo}
              data-testid="colaborador-papel"
            >
              <option value="colaborador">Colaborador</option>
              <option value="gerente">Gerente</option>
              <option value="admin">Admin</option>
              <option value="visualizador">Visualizador</option>
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Equipe
            <select value={form.departamento} onChange={(e) => setCampo('departamento', e.target.value)} className={campo}>
              <option value="">—</option>
              <option value="Criação">Criação</option>
              <option value="Atendimento">Atendimento</option>
              <option value="Mídia">Mídia</option>
              <option value="Comercial">Comercial</option>
              <option value="Direção">Direção</option>
              <option value="Operação">Operação</option>
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Custo/hora (R$)
            <input
              inputMode="numeric"
              value={form.custo_hora}
              onChange={(e) => setCampo('custo_hora', maskMoneyBR(e.target.value))}
              className={campo}
              placeholder="0,00"
              data-testid="colaborador-custo"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Carga semanal (h)
            <input
              type="number"
              min="1"
              max="80"
              value={form.carga_semanal_horas}
              onChange={(e) => setCampo('carga_semanal_horas', e.target.value)}
              className={campo}
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            {novo ? 'Senha de acesso' : 'Nova senha'}
            <input
              type="password"
              autoComplete="new-password"
              value={form.password}
              onChange={(e) => setCampo('password', e.target.value)}
              className={campo}
              placeholder={novo ? 'Mínimo 8 caracteres' : 'Preencha só para trocar'}
              data-testid="colaborador-senha"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Confirmar senha
            <input
              type="password"
              autoComplete="new-password"
              value={form.password_confirmation}
              onChange={(e) => setCampo('password_confirmation', e.target.value)}
              className={campo}
              data-testid="colaborador-senha-confirma"
            />
          </label>
        </div>

        <p className="mt-3 mb-0 text-xs font-medium text-[var(--muted)]">
          {novo
            ? 'Se a senha ficar vazia, a pessoa entra com a senha inicial padrão da agência. Convite por e-mail fica para um ciclo seguinte.'
            : 'Deixe a senha vazia para manter a atual. Só o admin/gerente redefine a senha de outra pessoa.'}
        </p>

        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}

        <div className="mt-5 flex flex-wrap items-center gap-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
          >
            {submitting ? 'Processando…' : novo ? 'Salvar colaborador' : 'Salvar alterações'}
          </button>
          <Link
            to="/colaboradores"
            className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]"
          >
            Cancelar
          </Link>
          {!novo && temPermissao(user, 'cadastrar_equipe') ? (
            <button
              type="button"
              onClick={() => setConfirmarExcluir(true)}
              disabled={submitting || excluindo}
              className="ml-auto rounded-lg border border-[#f3c4c0] px-4 py-2.5 text-sm font-bold text-[#9b1c1c]"
            >
              Excluir
            </button>
          ) : null}
        </div>
      </form>

      {confirmarExcluir ? (
        <div className="fixed inset-0 z-[60] grid place-items-center bg-black/30 p-4">
          <div className="w-full max-w-sm rounded-[12px] border border-[var(--line)] bg-white p-5">
            <p className="m-0 font-extrabold text-[var(--moss)]">Excluir este colaborador?</p>
            <p className="mt-2 mb-0 text-sm text-[var(--muted)]">
              Só funciona se a pessoa não tiver tarefas nem horas apontadas.
            </p>
            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                disabled={excluindo}
                onClick={() => setConfirmarExcluir(false)}
                className="rounded-lg border border-[var(--line)] px-3 py-2 text-sm font-bold"
              >
                Cancelar
              </button>
              <button
                type="button"
                data-testid="colaborador-confirmar-excluir"
                disabled={excluindo}
                onClick={onExcluir}
                className="rounded-lg bg-[#b42318] px-3 py-2 text-sm font-extrabold text-white disabled:opacity-70"
              >
                {excluindo ? 'Processando…' : 'Excluir colaborador'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </AppShell>
  )
}
