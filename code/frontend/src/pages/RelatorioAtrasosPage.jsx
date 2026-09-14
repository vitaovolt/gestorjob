import { useEffect, useState } from 'react'
import { getAtrasos, getTarefa } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import TaskDrawer from '../components/kanban/TaskDrawer.jsx'
import StatCard from '../components/relatorios/StatCard.jsx'
import { useToast } from '../context/ToastContext'
import { formatarData } from '../utils/format'

export default function RelatorioAtrasosPage() {
  const { showToast } = useToast()
  const [totais, setTotais] = useState({ no_prazo: 0, atrasadas: 0, sla: null })
  const [tarefas, setTarefas] = useState([])
  const [aberta, setAberta] = useState(null)
  const [erro, setErro] = useState('')
  const [carregando, setCarregando] = useState(true)

  async function recarregar() {
    const payload = await getAtrasos()
    setTotais(payload.data?.totais || { no_prazo: 0, atrasadas: 0, sla: null })
    setTarefas(payload.data?.tarefas || [])
  }

  useEffect(() => {
    recarregar()
      .then(() => setErro(''))
      .catch(() => {
        setErro('Não foi possível carregar os atrasos.')
        showToast('Não foi possível carregar os atrasos. Suba a API em :8000.', 'erro')
      })
      .finally(() => setCarregando(false))
  }, [showToast])

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
    recarregar().catch(() => {})
  }

  function onExcluida(id) {
    setAberta(null)
    setTarefas((lista) => lista.filter((item) => item.id !== id))
    recarregar().catch(() => {})
  }

  return (
    <AppShell title="Atrasos e prazos">
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <div className="mb-4 grid gap-3 sm:grid-cols-3">
        <StatCard valor={totais.no_prazo} label="No prazo" testid="stat-no-prazo" />
        <StatCard valor={totais.atrasadas} label="Atrasadas" tom="danger" testid="stat-atrasadas" />
        <StatCard valor={totais.sla == null ? '—' : `${totais.sla}%`} label="SLA" testid="stat-sla" />
      </div>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="relatorio-atrasos">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Tarefa</th>
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3">Prazo</th>
              <th className="px-4 py-3">Atraso</th>
            </tr>
          </thead>
          <tbody>
            {carregando ? (
              <tr>
                <td colSpan={4} className="px-4 py-10 text-center text-[var(--muted)]">
                  Carregando…
                </td>
              </tr>
            ) : tarefas.length === 0 ? (
              <tr>
                <td colSpan={4} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhuma tarefa atrasada.
                </td>
              </tr>
            ) : (
              tarefas.map((tarefa) => (
                <tr
                  key={tarefa.id}
                  data-testid={`atraso-tarefa-${tarefa.id}`}
                  onClick={() => abrir(tarefa)}
                  className="cursor-pointer border-b border-[var(--line)] bg-[#b42318]/10 last:border-0 hover:bg-[#b42318]/16"
                >
                  <td className="px-4 py-3 font-bold text-[var(--ink)]">{tarefa.titulo}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">{tarefa.cliente || '—'}</td>
                  <td className="px-4 py-3">{formatarData(tarefa.prazo_em)}</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-[#fdecea] px-2 py-0.5 text-[10px] font-bold text-[#b42318]">
                      {tarefa.dias_atraso} dia(s)
                    </span>
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
    </AppShell>
  )
}
