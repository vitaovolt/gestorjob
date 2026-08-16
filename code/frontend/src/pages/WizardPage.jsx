import { useRef, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { concluirWizard } from '../api/auth'
import { getAuthToken } from '../api/client'
import { createCliente, createColaborador, createServico } from '../api/dominio'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { maskMoneyBR, parseMoneyBR } from '../utils/masks'

const PASSOS = [
  { id: 1, titulo: 'Serviços', dica: 'Cadastre os serviços que a agência vende.' },
  { id: 2, titulo: 'Equipe', dica: 'Convide colaboradores e defina custo/hora.' },
  { id: 3, titulo: 'Clientes', dica: 'Inclua clientes com fee mensal.' },
  { id: 4, titulo: 'Feriados', dica: 'Calendário institucional fica para a fase 2 — pode pular.' },
  { id: 5, titulo: 'Permissões', dica: 'A matriz padrão já está aplicada. Confirme e abra o Kanban.' },
]

const campo =
  'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2.5 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

export default function WizardPage() {
  const { user, aplicarSessao } = useAuth()
  const navigate = useNavigate()
  const { showToast } = useToast()
  const actingRef = useRef(false)
  const [passo, setPasso] = useState(1)
  const [busy, setBusy] = useState('')
  const [erro, setErro] = useState('')

  const [servico, setServico] = useState({
    nome: 'Post feed Instagram',
    preco_venda: maskMoneyBR('280'),
    checklist_texto: 'Briefing\nArte\nCopy\nRevisão\nAgendar',
  })
  const [equipe, setEquipe] = useState({
    name: '',
    email: '',
    custo_hora: maskMoneyBR('70'),
    papel: 'colaborador',
  })
  const [cliente, setCliente] = useState({
    nome_fantasia: '',
    fee_mensal: maskMoneyBR('8000'),
    contato_nome: '',
    segmento: '',
  })

  if (!user?.wizard_pendente) {
    return <Navigate to="/" replace />
  }

  async function salvarPassoAtual() {
    if (passo === 1) {
      if (!servico.nome.trim()) {
        setErro('Informe o nome do serviço.')
        return false
      }
      await createServico({
        nome: servico.nome.trim(),
        preco_venda: parseMoneyBR(servico.preco_venda),
        checklist_padrao: servico.checklist_texto
          .split('\n')
          .map((l) => l.trim())
          .filter(Boolean),
      })
      showToast('Serviço cadastrado')
      return true
    }
    if (passo === 2) {
      if (!equipe.name.trim() || !equipe.email.trim()) {
        setErro('Informe nome e e-mail do colaborador.')
        return false
      }
      await createColaborador({
        name: equipe.name.trim(),
        email: equipe.email.trim().toLowerCase(),
        custo_hora: parseMoneyBR(equipe.custo_hora),
        papel: equipe.papel,
      })
      showToast('Colaborador cadastrado')
      return true
    }
    if (passo === 3) {
      if (!cliente.nome_fantasia.trim()) {
        setErro('Informe o nome do cliente.')
        return false
      }
      await createCliente({
        nome_fantasia: cliente.nome_fantasia.trim(),
        fee_mensal: parseMoneyBR(cliente.fee_mensal),
        contato_nome: cliente.contato_nome.trim() || null,
        segmento: cliente.segmento.trim() || null,
        status: 'ativo',
      })
      showToast('Cliente cadastrado')
      return true
    }
    return true
  }

  async function avancar({ salvar }) {
    if (actingRef.current) return
    actingRef.current = true
    setBusy(salvar ? 'salvar' : 'pular')
    setErro('')
    try {
      if (salvar) {
        const ok = await salvarPassoAtual()
        if (!ok) {
          actingRef.current = false
          setBusy('')
          return
        }
      }
      setPasso((p) => p + 1)
      actingRef.current = false
      setBusy('')
    } catch (err) {
      actingRef.current = false
      setBusy('')
      const msg =
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.nome?.[0] ||
        err.response?.data?.errors?.nome_fantasia?.[0] ||
        err.response?.data?.message ||
        'Não foi possível continuar.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  async function concluirAgora() {
    if (actingRef.current) return
    actingRef.current = true
    setBusy('concluir')
    setErro('')
    try {
      const payload = await concluirWizard()
      aplicarSessao(getAuthToken(), payload.data.user)
      showToast('Onboarding concluído')
      navigate('/', { replace: true })
    } catch (err) {
      actingRef.current = false
      setBusy('')
      const msg =
        err.response?.data?.errors?.wizard?.[0] ||
        err.response?.data?.message ||
        'Não foi possível concluir.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  const atual = PASSOS[passo - 1]

  return (
    <main className="mx-auto flex min-h-screen max-w-2xl flex-col justify-center px-5 py-10" data-testid="wizard-root">
      <h1 className="text-3xl font-extrabold tracking-tight text-[var(--moss)]">
        Gestor<span className="text-[var(--orange)]">Job</span>
      </h1>
      <p className="mt-1 text-sm text-[var(--muted)]">Onboarding · {user.empresa?.nome || 'sua agência'}</p>

      <div className="mt-6 flex flex-wrap gap-2" data-testid="wizard-passos">
        {PASSOS.map((p) => {
          const cls =
            p.id < passo
              ? 'border-[#b7d0c6] bg-[var(--moss-soft)] text-[var(--moss)]'
              : p.id === passo
                ? 'border-[#f5c4a4] bg-[var(--orange-soft)] text-[var(--orange)]'
                : 'border-[var(--line)] text-[var(--muted)]'
          return (
            <div key={p.id} className={`rounded-lg border px-2.5 py-1 text-xs font-bold ${cls}`}>
              {p.id}. {p.titulo}
            </div>
          )
        })}
      </div>

      <div className="mt-6 rounded-[10px] border border-[var(--line)] bg-[var(--surface)] p-5 shadow-[0_10px_30px_rgba(26,34,32,0.08)]">
        <h2 className="mt-0 mb-1 text-lg font-extrabold text-[var(--moss)]">{atual.titulo}</h2>
        <p className="mt-0 mb-4 text-sm text-[var(--muted)]">{atual.dica}</p>

        {passo === 1 ? (
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-sm font-bold text-[var(--moss)] sm:col-span-2">
              Serviço
              <input
                className={campo}
                value={servico.nome}
                onChange={(e) => setServico({ ...servico, nome: e.target.value })}
                data-testid="wizard-servico-nome"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Preço padrão
              <input
                className={campo}
                value={servico.preco_venda}
                onChange={(e) => setServico({ ...servico, preco_venda: maskMoneyBR(e.target.value) })}
                data-testid="wizard-servico-preco"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)] sm:col-span-2">
              Checklist padrão (uma linha por item)
              <textarea
                className={`${campo} min-h-[100px]`}
                value={servico.checklist_texto}
                onChange={(e) => setServico({ ...servico, checklist_texto: e.target.value })}
                data-testid="wizard-servico-checklist"
              />
            </label>
          </div>
        ) : null}

        {passo === 2 ? (
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-sm font-bold text-[var(--moss)]">
              Nome
              <input
                className={campo}
                value={equipe.name}
                onChange={(e) => setEquipe({ ...equipe, name: e.target.value })}
                data-testid="wizard-equipe-nome"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              E-mail
              <input
                type="email"
                className={campo}
                value={equipe.email}
                onChange={(e) => setEquipe({ ...equipe, email: e.target.value })}
                data-testid="wizard-equipe-email"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Custo/hora
              <input
                className={campo}
                value={equipe.custo_hora}
                onChange={(e) => setEquipe({ ...equipe, custo_hora: maskMoneyBR(e.target.value) })}
                data-testid="wizard-equipe-custo"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Papel
              <select
                className={campo}
                value={equipe.papel}
                onChange={(e) => setEquipe({ ...equipe, papel: e.target.value })}
                data-testid="wizard-equipe-papel"
              >
                <option value="colaborador">Colaborador</option>
                <option value="gerente">Gerente</option>
                <option value="visualizador">Visualizador</option>
              </select>
            </label>
          </div>
        ) : null}

        {passo === 3 ? (
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-sm font-bold text-[var(--moss)]">
              Cliente
              <input
                className={campo}
                value={cliente.nome_fantasia}
                onChange={(e) => setCliente({ ...cliente, nome_fantasia: e.target.value })}
                data-testid="wizard-cliente-nome"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Fee mensal
              <input
                className={campo}
                value={cliente.fee_mensal}
                onChange={(e) => setCliente({ ...cliente, fee_mensal: maskMoneyBR(e.target.value) })}
                data-testid="wizard-cliente-fee"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Contato
              <input
                className={campo}
                value={cliente.contato_nome}
                onChange={(e) => setCliente({ ...cliente, contato_nome: e.target.value })}
                data-testid="wizard-cliente-contato"
              />
            </label>
            <label className="block text-sm font-bold text-[var(--moss)]">
              Segmento
              <input
                className={campo}
                value={cliente.segmento}
                onChange={(e) => setCliente({ ...cliente, segmento: e.target.value })}
                data-testid="wizard-cliente-segmento"
              />
            </label>
          </div>
        ) : null}

        {passo === 4 ? (
          <div className="rounded-lg border border-dashed border-[var(--line)] p-4 text-sm text-[var(--muted)]">
            Feriados institucionais entram na fase 2 do produto. Por agora, pule este passo.
          </div>
        ) : null}

        {passo === 5 ? (
          <div className="space-y-2 text-sm text-[var(--ink)]">
            <p className="m-0 rounded-lg bg-[var(--moss-soft)] px-3 py-2 text-[var(--moss)]">
              Colaborador vê só tarefas alocadas · Financeiro só Admin/Gerente · Exclusão restrita.
            </p>
            <p className="m-0 text-[var(--muted)]">Você pode ajustar isso depois em Configurações e Permissões.</p>
          </div>
        ) : null}

        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}

        <div className="mt-5 flex flex-wrap gap-2">
          <button
            type="button"
            disabled={passo <= 1 || Boolean(busy)}
            onClick={() => setPasso((p) => Math.max(1, p - 1))}
            className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)] disabled:opacity-50"
          >
            Voltar
          </button>
          <span className="flex-1" />
          {passo < 5 ? (
            <>
              <button
                type="button"
                disabled={Boolean(busy)}
                data-testid="wizard-pular"
                onClick={() => avancar({ salvar: false })}
                className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)] disabled:opacity-70"
              >
                {busy === 'pular' ? 'Processando…' : 'Pular'}
              </button>
              {(passo === 1 || passo === 2 || passo === 3) && (
                <button
                  type="button"
                  disabled={Boolean(busy)}
                  data-testid="wizard-continuar"
                  onClick={() => avancar({ salvar: true })}
                  className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
                >
                  {busy === 'salvar' ? 'Processando…' : 'Salvar e continuar'}
                </button>
              )}
              {passo === 4 ? (
                <button
                  type="button"
                  disabled={Boolean(busy)}
                  data-testid="wizard-continuar"
                  onClick={() => avancar({ salvar: false })}
                  className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
                >
                  Continuar
                </button>
              ) : null}
            </>
          ) : (
            <button
              type="button"
              disabled={Boolean(busy)}
              data-testid="wizard-concluir"
              onClick={concluirAgora}
              className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
            >
              {busy === 'concluir' ? 'Processando…' : 'Concluir e abrir Kanban'}
            </button>
          )}
        </div>
      </div>
    </main>
  )
}
