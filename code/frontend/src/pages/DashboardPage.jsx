import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getAtrasos, getCarga, getHoras, getMargem } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import BarraCarga, { tomCarga } from '../components/relatorios/BarraCarga.jsx'
import FiltroCompetencia from '../components/relatorios/FiltroCompetencia.jsx'
import StatCard from '../components/relatorios/StatCard.jsx'
import { useToast } from '../context/ToastContext'
import { competenciaAtual, formatarBRL, rotuloCompetencia } from '../utils/format'

function tomMargem(margem) {
  const n = Number(margem)
  if (!Number.isFinite(n)) return ''
  if (n < 0) return 'text-[#b42318]'
  if (n < 1000) return 'text-[#b86a14]'
  return 'text-[#1f7a4d]'
}

function tomEstimado(percentual) {
  const n = Number(percentual)
  if (!Number.isFinite(n)) return 'ok'
  if (n > 100) return 'danger'
  if (n >= 80) return 'warn'
  return 'ok'
}

export default function DashboardPage() {
  const { showToast } = useToast()
  const [competencia, setCompetencia] = useState(competenciaAtual)
  const [margem, setMargem] = useState([])
  const [atrasos, setAtrasos] = useState({ no_prazo: 0, atrasadas: 0, sla: null })
  const [estimado, setEstimado] = useState([])
  const [carga, setCarga] = useState({ capacidade_media: null, colaboradores: [] })
  const [erro, setErro] = useState('')
  const [carregando, setCarregando] = useState(true)

  useEffect(() => {
    let ativo = true
    setCarregando(true)
    Promise.all([getMargem(competencia), getAtrasos(), getHoras(competencia), getCarga()])
      .then(([resMargem, resAtrasos, resHoras, resCarga]) => {
        if (!ativo) return
        setMargem(resMargem.data?.clientes || [])
        setAtrasos(resAtrasos.data?.totais || { no_prazo: 0, atrasadas: 0, sla: null })
        setEstimado(resHoras.data?.estimado_vs_real || [])
        setCarga({
          capacidade_media: resCarga.data?.capacidade_media ?? null,
          colaboradores: resCarga.data?.colaboradores || [],
        })
        setErro('')
      })
      .catch(() => {
        if (!ativo) return
        setErro('Não foi possível carregar o dashboard.')
        showToast('Não foi possível carregar o dashboard. Suba a API em :8000.', 'erro')
      })
      .finally(() => {
        if (ativo) setCarregando(false)
      })
    return () => {
      ativo = false
    }
  }, [competencia, showToast])

  const margemMes = margem.reduce((acc, linha) => acc + Number(linha.margem || 0), 0)
  const negativos = margem.filter((linha) => Number(linha.margem) < 0)

  return (
    <AppShell title="Dashboard">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p className="m-0 text-sm text-[var(--muted)]">
          {rotuloCompetencia(competencia)} · margem, prazo e carga.
        </p>
        <FiltroCompetencia value={competencia} onChange={setCompetencia} />
      </div>

      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}

      <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" data-testid="dashboard-page">
        <StatCard valor={atrasos.no_prazo} label="No prazo" to="/relatorios/atrasos" testid="dash-stat-prazo" />
        <StatCard
          valor={atrasos.atrasadas}
          label="Atrasadas"
          to="/relatorios/atrasos"
          tom="danger"
          testid="dash-stat-atrasadas"
        />
        <StatCard
          valor={formatarBRL(margemMes)}
          label="Margem do mês"
          to="/relatorios/margem"
          tom="action"
          testid="dash-stat-margem"
        />
        <StatCard
          valor={carga.capacidade_media == null ? '—' : `${carga.capacidade_media}%`}
          label="Capacidade média"
          to="/relatorios/carga"
          tom={Number(carga.capacidade_media) > 90 ? 'danger' : Number(carga.capacidade_media) > 80 ? 'warn' : 'padrao'}
          testid="dash-stat-carga"
        />
      </div>

      <div className="mt-4 grid gap-3 lg:grid-cols-2">
        <div className="overflow-hidden rounded-[12px] border border-[var(--line)] bg-white">
          <div className="flex items-center justify-between border-b border-[var(--line)] px-4 py-3">
            <strong>Margem por cliente</strong>
            <Link to="/relatorios/margem" className="text-xs font-extrabold text-[var(--moss)] hover:underline">
              Ver relatório
            </Link>
          </div>
          <div className="overflow-auto">
            <table className="w-full min-w-[420px] border-collapse text-left text-sm">
              <thead>
                <tr className="border-b border-[var(--line)] text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
                  <th className="px-4 py-2">Cliente</th>
                  <th className="px-4 py-2 text-right">Fee</th>
                  <th className="px-4 py-2 text-right">Custo</th>
                  <th className="px-4 py-2 text-right">Margem</th>
                </tr>
              </thead>
              <tbody>
                {carregando ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-8 text-center text-[var(--muted)]">
                      Carregando…
                    </td>
                  </tr>
                ) : margem.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="px-4 py-8 text-center text-[var(--muted)]">
                      Sem clientes ativos.
                    </td>
                  </tr>
                ) : (
                  margem.map((linha) => (
                    <tr key={linha.cliente_id} className="border-b border-[var(--line)] last:border-0">
                      <td className={`px-4 py-2 font-bold ${tomMargem(linha.margem)}`}>{linha.nome}</td>
                      <td className="px-4 py-2 text-right">{formatarBRL(linha.fee)}</td>
                      <td className="px-4 py-2 text-right">{formatarBRL(linha.custo)}</td>
                      <td className={`px-4 py-2 text-right font-extrabold ${tomMargem(linha.margem)}`}>
                        {formatarBRL(linha.margem)}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          {negativos.length > 0 ? (
            <div className="m-3 rounded-[10px] border border-[#f3c4c0] bg-[#fdecea] px-3 py-2 text-sm text-[#b42318]">
              {negativos.map((linha) => linha.nome).join(', ')} com margem negativa — revisar escopo e correções.
            </div>
          ) : null}
        </div>

        <div className="rounded-[12px] border border-[var(--line)] bg-white">
          <div className="flex items-center justify-between border-b border-[var(--line)] px-4 py-3">
            <strong>Estimado vs real</strong>
            <Link to="/relatorios/horas" className="text-xs font-extrabold text-[var(--moss)] hover:underline">
              Horas
            </Link>
          </div>
          <div className="flex flex-col gap-3 p-4">
            {carregando ? <p className="m-0 text-sm text-[var(--muted)]">Carregando…</p> : null}
            {!carregando && estimado.length === 0 ? (
              <p className="m-0 text-sm text-[var(--muted)]">Sem apontamentos nesta competência.</p>
            ) : null}
            {estimado.map((item, idx) => {
              const tom = tomEstimado(item.percentual)
              const label = [item.servico, item.cliente].filter(Boolean).join(' / ') || 'Serviço'
              return (
                <div key={`${item.cliente}-${item.servico}-${idx}`}>
                  <div className="flex text-sm">
                    <strong>{label}</strong>
                    <span className="flex-1" />
                    <span className="text-[var(--muted)]">
                      {item.percentual == null ? 'sem estimado' : `${item.percentual}% do estimado`}
                    </span>
                  </div>
                  <div className="mt-1.5">
                    <BarraCarga percentual={item.percentual} tom={tom} />
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>

      <div className="mt-3 rounded-[12px] border border-[var(--line)] bg-white">
        <div className="border-b border-[var(--line)] px-4 py-3">
          <strong>Carga por colaborador</strong>
        </div>
        <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
          {carga.colaboradores.map((pessoa) => (
            <div key={pessoa.user_id} className="rounded-[10px] border border-[var(--line)] px-3 py-3">
              <div className="text-base font-extrabold text-[var(--moss)]">{pessoa.nome.split(' ')[0]}</div>
              <div className="text-xs text-[var(--muted)]">{pessoa.departamento || '—'}</div>
              <div className="mt-2">
                <BarraCarga percentual={pessoa.percentual} />
              </div>
              <div className="mt-1 text-[10px] font-bold text-[var(--muted)]">
                {pessoa.percentual == null ? 'Sem carga semanal' : `${pessoa.percentual}% da capacidade`}
              </div>
            </div>
          ))}
        </div>
      </div>
    </AppShell>
  )
}
