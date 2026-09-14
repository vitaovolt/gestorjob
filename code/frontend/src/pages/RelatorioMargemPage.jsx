import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getMargem } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import FiltroCompetencia from '../components/relatorios/FiltroCompetencia.jsx'
import { useToast } from '../context/ToastContext'
import { competenciaAtual, formatarBRL, rotuloCompetencia } from '../utils/format'

function tomMargem(margem) {
  const n = Number(margem)
  if (!Number.isFinite(n)) return ''
  if (n < 0) return 'text-[#b42318]'
  if (n < 1000) return 'text-[#b86a14]'
  return 'text-[#1f7a4d]'
}

export default function RelatorioMargemPage() {
  const { showToast } = useToast()
  const [competencia, setCompetencia] = useState(competenciaAtual)
  const [linhas, setLinhas] = useState([])
  const [erro, setErro] = useState('')
  const [carregando, setCarregando] = useState(true)

  useEffect(() => {
    let ativo = true
    setCarregando(true)
    getMargem(competencia)
      .then((payload) => {
        if (!ativo) return
        setLinhas(payload.data?.clientes || [])
        setErro('')
      })
      .catch(() => {
        if (!ativo) return
        setErro('Não foi possível carregar a margem.')
        showToast('Não foi possível carregar a margem. Suba a API em :8000.', 'erro')
      })
      .finally(() => {
        if (ativo) setCarregando(false)
      })
    return () => {
      ativo = false
    }
  }, [competencia, showToast])

  const negativos = linhas.filter((linha) => Number(linha.margem) < 0)

  return (
    <AppShell title="Relatório de margem">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p className="m-0 text-sm text-[var(--muted)]">
          Fee − Σ(horas × custo/hora) · {rotuloCompetencia(competencia)}
        </p>
        <div className="flex items-center gap-3">
          <FiltroCompetencia value={competencia} onChange={setCompetencia} />
          <Link to="/dashboard" className="text-sm font-extrabold text-[var(--moss)] hover:underline">
            Dashboard
          </Link>
        </div>
      </div>

      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      {negativos.length > 0 ? (
        <div className="mb-3 rounded-[10px] border border-[#f3c4c0] bg-[#fdecea] px-3 py-2 text-sm text-[#b42318]">
          <strong>Alerta</strong>{' '}
          {negativos.map((linha) => `${linha.nome}: ${formatarBRL(linha.margem)}`).join(' · ')}
        </div>
      ) : null}

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="relatorio-margem">
        <table className="w-full min-w-[720px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3 text-right">Horas</th>
              <th className="px-4 py-3 text-right">Custo</th>
              <th className="px-4 py-3 text-right">Fee</th>
              <th className="px-4 py-3 text-right">Margem</th>
              <th className="px-4 py-3 text-right">%</th>
            </tr>
          </thead>
          <tbody>
            {carregando ? (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-[var(--muted)]">
                  Carregando…
                </td>
              </tr>
            ) : linhas.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhum cliente ativo nesta competência.
                </td>
              </tr>
            ) : (
              linhas.map((linha) => (
                <tr key={linha.cliente_id} className="border-b border-[var(--line)] last:border-0">
                  <td className={`px-4 py-3 font-bold ${tomMargem(linha.margem)}`}>{linha.nome}</td>
                  <td className="px-4 py-3 text-right text-[var(--muted)]">
                    {Number(linha.horas).toLocaleString('pt-BR')}
                  </td>
                  <td className="px-4 py-3 text-right">{formatarBRL(linha.custo)}</td>
                  <td className="px-4 py-3 text-right">{formatarBRL(linha.fee)}</td>
                  <td className={`px-4 py-3 text-right font-extrabold ${tomMargem(linha.margem)}`}>
                    {formatarBRL(linha.margem)}
                  </td>
                  <td className="px-4 py-3 text-right text-[var(--muted)]">
                    {linha.margem_percentual == null ? '—' : `${linha.margem_percentual}%`}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </AppShell>
  )
}
