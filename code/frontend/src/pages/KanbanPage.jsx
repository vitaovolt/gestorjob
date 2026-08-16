import { useEffect, useRef, useState } from 'react'
import { getTarefa, listClientes, listServicos, listTarefas, updateTarefa } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import CreateTaskModal from '../components/kanban/CreateTaskModal.jsx'
import TaskDrawer from '../components/kanban/TaskDrawer.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { temPermissao } from '../utils/format'
import { COLUNAS, PRIORIDADE_LABEL } from './kanbanLabels'

export default function KanbanPage() {
  const { user } = useAuth()
  const { showToast } = useToast()
  const [tarefas, setTarefas] = useState([])
  const [clientes, setClientes] = useState([])
  const [servicos, setServicos] = useState([])
  const [aberta, setAberta] = useState(null)
  const [criar, setCriar] = useState(false)
  const [erro, setErro] = useState('')
  const [overCol, setOverCol] = useState(null)
  const movingRef = useRef(false)
  const draggedRef = useRef(false)

  async function recarregar() {
    const [t, c, s] = await Promise.all([listTarefas(), listClientes(), listServicos()])
    setTarefas(t.data || [])
    setClientes(c.data || [])
    setServicos(s.data || [])
  }

  useEffect(() => {
    recarregar().catch(() => {
      setErro('Não foi possível carregar o quadro. Confira se a API está no ar.')
      showToast('Não foi possível carregar o Kanban. Suba a API em :8000.', 'erro')
    })
  }, [])

  async function abrir(tarefa) {
    if (draggedRef.current) {
      draggedRef.current = false
      return
    }
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

  async function moverCard(tarefaId, status) {
    const atual = tarefas.find((t) => t.id === Number(tarefaId))
    if (!atual || atual.status === status) return
    if (movingRef.current) return
    movingRef.current = true
    try {
      const payload = await updateTarefa(atual.id, { status })
      const tarefa = payload.data
      setTarefas((lista) => lista.map((item) => (item.id === tarefa.id ? { ...item, ...tarefa } : item)))
      if (aberta?.id === tarefa.id) setAberta(tarefa)
      showToast('Tarefa movida')
    } catch {
      showToast('Não foi possível mover a tarefa.', 'erro')
    } finally {
      movingRef.current = false
    }
  }

  function onDragStart(event, tarefa) {
    draggedRef.current = true
    event.dataTransfer.setData('text/plain', String(tarefa.id))
    event.dataTransfer.effectAllowed = 'move'
  }

  function onDragOver(event, colId) {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
    setOverCol(colId)
  }

  function onDrop(event, status) {
    event.preventDefault()
    setOverCol(null)
    const id = event.dataTransfer.getData('text/plain')
    if (id) moverCard(id, status)
  }

  return (
    <AppShell
      title="Kanban"
      cta={temPermissao(user, 'criar_tarefas') ? { label: '+ Tarefa', onClick: () => setCriar(true) } : undefined}
    >
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Arraste o card para outra coluna, ou clique para abrir o timer.
      </p>
      <div className="flex min-h-[70vh] gap-3 overflow-x-auto pb-4" data-testid="kanban-board">
        {COLUNAS.map((col) => {
          const cards = tarefas.filter((t) => t.status === col.id)
          const recebendo = overCol === col.id
          return (
            <section
              key={col.id}
              data-testid={`coluna-${col.id}`}
              onDragOver={(e) => onDragOver(e, col.id)}
              onDragLeave={() => setOverCol((atual) => (atual === col.id ? null : atual))}
              onDrop={(e) => onDrop(e, col.id)}
              className={`flex w-[260px] shrink-0 flex-col rounded-[12px] border p-2 transition-colors ${
                recebendo
                  ? 'border-[var(--orange)] bg-[var(--orange-soft)]'
                  : 'border-[var(--line)] bg-[var(--moss-soft)]/40'
              }`}
            >
              <header className="flex items-center justify-between px-1 py-2">
                <h2 className="m-0 text-sm font-extrabold text-[var(--moss)]">{col.label}</h2>
                <span className="text-xs font-bold text-[var(--muted)]">{cards.length}</span>
              </header>
              <div className="flex min-h-[120px] flex-col gap-2">
                {cards.length === 0 ? (
                  <p className="m-0 rounded-lg border border-dashed border-[var(--line)] bg-white/70 px-3 py-6 text-center text-xs text-[var(--muted)]">
                    Solte aqui
                  </p>
                ) : (
                  cards.map((tarefa) => (
                    <button
                      key={tarefa.id}
                      type="button"
                      draggable
                      data-testid={`card-${tarefa.id}`}
                      onDragStart={(e) => onDragStart(e, tarefa)}
                      onDragEnd={() => {
                        setOverCol(null)
                        window.setTimeout(() => {
                          draggedRef.current = false
                        }, 0)
                      }}
                      onClick={() => abrir(tarefa)}
                      className="cursor-grab rounded-[10px] border border-[var(--line)] bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[var(--orange)] hover:shadow-md active:cursor-grabbing"
                    >
                      <p className="m-0 text-sm font-extrabold text-[var(--ink)]">{tarefa.titulo}</p>
                      <p className="mt-1 mb-0 text-xs text-[var(--muted)]">{tarefa.cliente?.nome_fantasia}</p>
                      <div className="mt-2 flex flex-wrap gap-1">
                        <span className="rounded-full bg-[var(--orange-soft)] px-2 py-0.5 text-[10px] font-bold text-[var(--orange)]">
                          {PRIORIDADE_LABEL[tarefa.prioridade] || tarefa.prioridade}
                        </span>
                        {tarefa.atrasada ? (
                          <span className="rounded-full bg-[#fdecea] px-2 py-0.5 text-[10px] font-bold text-[#b42318]">
                            Atrasado
                          </span>
                        ) : null}
                      </div>
                    </button>
                  ))
                )}
              </div>
            </section>
          )
        })}
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
