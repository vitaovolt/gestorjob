import { useEffect, useState } from 'react'
import { getCarga } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import BarraCarga, { tomCarga } from '../components/relatorios/BarraCarga.jsx'
import { useToast } from '../context/ToastContext'
import { formatarHoras, papelLabel } from '../utils/format'

const PILL = {
  ok: 'bg-[var(--moss-soft)] text-[var(--moss)]',
  warn: 'bg-[#fff4e5] text-[#b86a14]',
  danger: 'bg-[#fdecea] text-[#b42318]',
}

export default function RelatorioCargaPage() {
  const { showToast } = useToast()
  const [colaboradores, setColaboradores] = useState([])
  const [erro, setErro] = useState('')
  const [carregando, setCarregando] = useState(true)

  useEffect(() => {
    getCarga()
      .then((payload) => {
        setColaboradores(payload.data?.colaboradores || [])
        setErro('')
      })
      .catch(() => {
        setErro('Não foi possível carregar a carga.')
        showToast('Não foi possível carregar a carga. Suba a API em :8000.', 'erro')
      })
      .finally(() => setCarregando(false))
  }, [showToast])

  return (
    <AppShell title="Carga de trabalho">
      <p className="mt-0 mb-4 text-sm text-[var(--muted)]">% da capacidade semanal da equipe.</p>
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}

      {carregando ? <p className="text-[var(--muted)]">Carregando…</p> : null}

      {!carregando && colaboradores.length === 0 ? (
        <p className="text-[var(--muted)]">Nenhum colaborador com carga cadastrada.</p>
      ) : null}

      <div className="grid gap-3 md:grid-cols-2" data-testid="relatorio-carga">
        {colaboradores.map((pessoa) => {
          const tom = tomCarga(pessoa.percentual)
          return (
            <div key={pessoa.user_id} className="rounded-[12px] border border-[var(--line)] bg-white p-4">
              <div className="flex items-center gap-2">
                <strong className="text-[var(--ink)]">{pessoa.nome}</strong>
                <span className="flex-1" />
                <span className={`rounded-full px-2 py-0.5 text-[10px] font-extrabold ${PILL[tom]}`}>
                  {pessoa.percentual == null ? '—' : `${pessoa.percentual}%`}
                </span>
              </div>
              <p className="mt-1 mb-3 text-xs text-[var(--muted)]">
                {pessoa.departamento || '—'} · {papelLabel(pessoa.papel)} · {formatarHoras(pessoa.horas)}
                {pessoa.carga_semanal_horas ? ` de ${pessoa.carga_semanal_horas}h` : ''}
              </p>
              <BarraCarga percentual={pessoa.percentual} tom={tom} />
            </div>
          )
        })}
      </div>
    </AppShell>
  )
}
