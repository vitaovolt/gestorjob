import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteServico, listServicos } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import AcoesLista from '../components/ui/AcoesLista.jsx'
import ConfirmarExcluir from '../components/ui/ConfirmarExcluir.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatarBRL, formatarMinutos, podeGerirCadastros } from '../utils/format'

function recorrenciaLabel(recorrencia) {
  if (!recorrencia?.frequencia) return null
  return 'Ativa'
}

export default function ServicosPage() {
  const { user } = useAuth()
  const podeGerir = podeGerirCadastros(user)
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [servicos, setServicos] = useState([])
  const [erro, setErro] = useState('')
  const [alvo, setAlvo] = useState(null)
  const [excluindo, setExcluindo] = useState(false)

  useEffect(() => {
    listServicos()
      .then((payload) => setServicos(payload.data || []))
      .catch(() => {
        setErro('Não foi possível carregar os serviços.')
        showToast('Não foi possível carregar os serviços. Suba a API em :8000.', 'erro')
      })
  }, [])

  async function onExcluir() {
    if (submittingRef.current || !alvo) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteServico(alvo.id)
      setServicos((lista) => lista.filter((s) => s.id !== alvo.id))
      showToast('Serviço removido')
      setAlvo(null)
    } catch (err) {
      const msg = err.response?.data?.message || 'Não foi possível excluir o serviço.'
      showToast(msg, 'erro')
      setAlvo(null)
    } finally {
      submittingRef.current = false
      setExcluindo(false)
    }
  }

  return (
    <AppShell
      title="Serviços"
      cta={podeGerir ? { label: '+ Serviço', to: '/servicos/novo' } : undefined}
    >
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Escopo, preço e template de recorrência.
        {podeGerir ? (
          <>
            {' '}
            Use <strong>Editar</strong> ou <strong>Excluir</strong>.
          </>
        ) : null}
      </p>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="lista-servicos">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Serviço</th>
              <th className="px-4 py-3 text-right">Preço</th>
              <th className="px-4 py-3">Estimativa</th>
              <th className="px-4 py-3">Recorrência</th>
              {podeGerir ? <th className="px-4 py-3 text-right">Ações</th> : null}
            </tr>
          </thead>
          <tbody>
            {servicos.length === 0 ? (
              <tr>
                <td colSpan={podeGerir ? 5 : 4} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhum serviço ainda.
                  {podeGerir ? ' Use + Serviço.' : null}
                </td>
              </tr>
            ) : (
              servicos.map((servico) => (
                <tr key={servico.id} className="border-b border-[var(--line)] last:border-0 hover:bg-[var(--moss-soft)]/40">
                  <td className="px-4 py-3">
                    {podeGerir ? (
                      <Link
                        to={`/servicos/${servico.id}`}
                        className="font-extrabold text-[var(--moss)] hover:text-[var(--orange)]"
                      >
                        {servico.nome}
                      </Link>
                    ) : (
                      <span className="font-extrabold text-[var(--moss)]">{servico.nome}</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right font-bold">{formatarBRL(servico.preco_venda)}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">{formatarMinutos(servico.tempo_estimado_minutos)}</td>
                  <td className="px-4 py-3">
                    {recorrenciaLabel(servico.recorrencia) ? (
                      <span className="rounded-full bg-[var(--orange-soft)] px-2 py-0.5 text-xs font-bold text-[var(--orange)]">
                        Ativa
                      </span>
                    ) : (
                      <span className="text-[var(--muted)]">—</span>
                    )}
                  </td>
                  {podeGerir ? (
                    <td className="px-4 py-3 text-right">
                      <AcoesLista to={`/servicos/${servico.id}`} nome={servico.nome} onExcluir={() => setAlvo(servico)} />
                    </td>
                  ) : null}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {alvo ? (
        <ConfirmarExcluir
          titulo={`Excluir ${alvo.nome}?`}
          texto="Só funciona se não houver tarefas ligadas a este serviço."
          confirmarLabel="Excluir serviço"
          processando={excluindo}
          onCancelar={() => setAlvo(null)}
          onConfirmar={onExcluir}
        />
      ) : null}
    </AppShell>
  )
}
