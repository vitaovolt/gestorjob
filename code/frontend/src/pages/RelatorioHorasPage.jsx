import { useEffect, useState } from 'react'
import { getHoras } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import FiltroCompetencia from '../components/relatorios/FiltroCompetencia.jsx'
import { useToast } from '../context/ToastContext'
import { FASES_TIMER } from './kanbanLabels'
import { competenciaAtual, formatarBRL, formatarHoras, rotuloCompetencia } from '../utils/format'

function rotuloFase(fase) {
  return FASES_TIMER.find((item) => item.id === fase)?.label || fase || '—'
}

export default function RelatorioHorasPage() {
  const { showToast } = useToast()
  const [competencia, setCompetencia] = useState(competenciaAtual)
  const [linhas, setLinhas] = useState([])
  const [erro, setErro] = useState('')
  const [carregando, setCarregando] = useState(true)

  useEffect(() => {
    let ativo = true
    setCarregando(true)
    getHoras(competencia)
      .then((payload) => {
        if (!ativo) return
        setLinhas(payload.data?.linhas || [])
        setErro('')
      })
      .catch(() => {
        if (!ativo) return
        setErro('Não foi possível carregar as horas.')
        showToast('Não foi possível carregar as horas. Suba a API em :8000.', 'erro')
      })
      .finally(() => {
        if (ativo) setCarregando(false)
      })
    return () => {
      ativo = false
    }
  }, [competencia, showToast])

  return (
    <AppShell title="Horas por colaborador/cliente">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p className="m-0 text-sm text-[var(--muted)]">
          Apontamentos encerrados em {rotuloCompetencia(competencia)}.
        </p>
        <FiltroCompetencia value={competencia} onChange={setCompetencia} />
      </div>

      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="relatorio-horas">
        <table className="w-full min-w-[720px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Colaborador</th>
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3">Fase</th>
              <th className="px-4 py-3 text-right">Horas</th>
              <th className="px-4 py-3 text-right">Custo</th>
            </tr>
          </thead>
          <tbody>
            {carregando ? (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-[var(--muted)]">
                  Carregando…
                </td>
              </tr>
            ) : linhas.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhum apontamento nesta competência.
                </td>
              </tr>
            ) : (
              linhas.map((linha, idx) => (
                <tr key={`${linha.user_id}-${linha.cliente_id}-${linha.fase}-${idx}`} className="border-b border-[var(--line)] last:border-0">
                  <td className="px-4 py-3 font-bold">{linha.colaborador || '—'}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">{linha.cliente || '—'}</td>
                  <td className="px-4 py-3">{rotuloFase(linha.fase)}</td>
                  <td className="px-4 py-3 text-right">{formatarHoras(linha.horas)}</td>
                  <td className="px-4 py-3 text-right">{formatarBRL(linha.custo)}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </AppShell>
  )
}
