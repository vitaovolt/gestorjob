import { useEffect, useRef, useState } from 'react'
import { getTarefa, listClientes, listServicos, listTarefas } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import CreateTaskModal from '../components/kanban/CreateTaskModal.jsx'
import TaskDrawer from '../components/kanban/TaskDrawer.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatarData, temPermissao } from '../utils/format'
import { COLUNAS, PRIORIDADE_LABEL } from './kanbanLabels'

export default function ListaPage() {
  const { user } = useAuth()
  const { showToast } = useToast()
  const [tarefas, setTarefas] = useState([])
  const [clientes, setClientes] = useState([])
  const [servicos, setServicos] = useState([])
  const [aberta, setAberta] = useState(null)
  const [criar, setCriar] = useState(false)
  const [busca, setBusca] = useState('')
  const [erro, setErro] = useState('')
  const loaded = useRef(false)

  async function recarregar() {
    const [t, c, s] = await Promise.all([listTarefas(), listClientes(), listServicos()])
    setTarefas(t.data || [])
    setClientes(c.data || [])
    setServicos(s.data || [])
  }

  useEffect(() => {
    if (loaded.current) return
    loaded.current = true
    recarregar().catch(() => {
      setErro('Não foi possível carregar a lista. Confira se a API está no ar.')
      showToast('Não foi possível carregar as tarefas. Suba a API em :8000.', 'erro')
    })
  }, [])

  async function abrir(tarefa) {
    try {
      const payload = await getTarefa(tarefa.id)
      setAberta(payload.data)
    } catch {
      showToast('Não foi possível abrir a tarefa.', 'erro')
    }
  }

  function onAtualizada(tarefa) {
    setAberta(tarefa)
    setTarefas((lista) => lista.map((item) => (item.id === tarefa.id ? { ...item, ...tarefa } : item)))
  }

  function onCriada(tarefa) {
    setTarefas((lista) => [tarefa, ...lista])
    setCriar(false)
    setAberta(tarefa)
  }

  function onExcluida(id) {
    setAberta(null)
    setTarefas((lista) => lista.filter((item) => item.id !== id))
  }

  const termo = busca.trim().toLowerCase()
  const filtradas = termo
    ? tarefas.filter((t) => {
        const hay = `${t.titulo} ${t.cliente?.nome_fantasia || ''}`.toLowerCase()
        return hay.includes(termo)
      })
    : tarefas

  return (
    <AppShell
      title="Lista de tarefas"
      cta={temPermissao(user, 'criar_tarefas') ? { label: '+ Tarefa', onClick: () => setCriar(true) } : undefined}
    >
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <div className="mb-3 flex items-center gap-3">
        <input
          type="search"
          value={busca}
          onChange={(e) => setBusca(e.target.value)}
          placeholder="Buscar por tarefa ou cliente"
          className="w-full max-w-sm rounded-lg border border-[var(--line)] px-3 py-2 text-sm"
        />
        <span className="text-xs font-bold text-[var(--muted)]">{filtradas.length} tarefa(s)</span>
      </div>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="lista-tarefas">
        <table className="w-full min-w-[720px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Tarefa</th>
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Prioridade</th>
              <th className="px-4 py-3">Prazo</th>
              <th className="px-4 py-3">Resp.</th>
            </tr>
          </thead>
          <tbody>
            {filtradas.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhuma tarefa nesta visão.
                </td>
              </tr>
            ) : (
              filtradas.map((tarefa) => (
                <tr
                  key={tarefa.id}
                  data-testid={`lista-tarefa-${tarefa.id}`}
                  onClick={() => abrir(tarefa)}
                  className="cursor-pointer border-b border-[var(--line)] last:border-0 hover:bg-[var(--moss-soft)]/40"
                >
                  <td className="px-4 py-3">
                    <strong className="text-[var(--ink)]">{tarefa.titulo}</strong>
                    {tarefa.atrasada ? (
                      <span className="ml-2 rounded-full bg-[#fdecea] px-2 py-0.5 text-[10px] font-bold text-[#b42318]">
                        Atrasado
                      </span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">{tarefa.cliente?.nome_fantasia || '—'}</td>
                  <td className="px-4 py-3">{COLUNAS.find((c) => c.id === tarefa.status)?.label || tarefa.status}</td>
                  <td className="px-4 py-3">{PRIORIDADE_LABEL[tarefa.prioridade] || tarefa.prioridade}</td>
                  <td className="px-4 py-3">{formatarData(tarefa.prazo_em)}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">
                    {(tarefa.responsaveis || []).map((p) => p.name).join(', ') || '—'}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {aberta ? (
        <TaskDrawer tarefa={aberta} onClose={() => setAberta(null)} onAtualizada={onAtualizada} onExcluida={onExcluida} />
      ) : null}
      {criar ? (
        <CreateTaskModal
          clientes={clientes}
          servicos={servicos}
          onClose={() => setCriar(false)}
          onCriada={onCriada}
        />
      ) : null}
    </AppShell>
  )
}
