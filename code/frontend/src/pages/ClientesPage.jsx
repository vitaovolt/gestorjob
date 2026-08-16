import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteCliente, listClientes } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import AcoesLista from '../components/ui/AcoesLista.jsx'
import ConfirmarExcluir from '../components/ui/ConfirmarExcluir.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatarBRL, podeGerirCadastros } from '../utils/format'

const STATUS_LABEL = {
  ativo: 'Ativo',
  inativo: 'Inativo',
  prospect: 'Prospect',
}

export default function ClientesPage() {
  const { user } = useAuth()
  const podeGerir = podeGerirCadastros(user)
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [clientes, setClientes] = useState([])
  const [erro, setErro] = useState('')
  const [alvo, setAlvo] = useState(null)
  const [excluindo, setExcluindo] = useState(false)

  useEffect(() => {
    listClientes()
      .then((payload) => setClientes(payload.data || []))
      .catch(() => {
        setErro('Não foi possível carregar os clientes.')
        showToast('Não foi possível carregar os clientes. Suba a API em :8000.', 'erro')
      })
  }, [])

  async function onExcluir() {
    if (submittingRef.current || !alvo) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteCliente(alvo.id)
      setClientes((lista) => lista.filter((c) => c.id !== alvo.id))
      showToast('Cliente removido')
      setAlvo(null)
    } catch (err) {
      const msg = err.response?.data?.message || 'Não foi possível excluir o cliente.'
      showToast(msg, 'erro')
      setAlvo(null)
    } finally {
      submittingRef.current = false
      setExcluindo(false)
    }
  }

  return (
    <AppShell
      title="Clientes"
      cta={podeGerir ? { label: '+ Cliente', to: '/clientes/novo' } : undefined}
    >
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Fee, contato e status.
        {podeGerir ? (
          <>
            {' '}
            Use <strong>Editar</strong> ou <strong>Excluir</strong>.
          </>
        ) : null}
      </p>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="lista-clientes">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Cliente</th>
              <th className="px-4 py-3">Segmento</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Contato</th>
              <th className="px-4 py-3 text-right">Fee</th>
              {podeGerir ? <th className="px-4 py-3 text-right">Ações</th> : null}
            </tr>
          </thead>
          <tbody>
            {clientes.length === 0 ? (
              <tr>
                <td colSpan={podeGerir ? 6 : 5} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhum cliente ainda.
                  {podeGerir ? ' Use + Cliente.' : null}
                </td>
              </tr>
            ) : (
              clientes.map((cliente) => (
                <tr key={cliente.id} className="border-b border-[var(--line)] last:border-0 hover:bg-[var(--moss-soft)]/40">
                  <td className="px-4 py-3">
                    {podeGerir ? (
                      <Link
                        to={`/clientes/${cliente.id}`}
                        className="font-extrabold text-[var(--moss)] hover:text-[var(--orange)]"
                      >
                        {cliente.nome_fantasia}
                      </Link>
                    ) : (
                      <span className="font-extrabold text-[var(--moss)]">{cliente.nome_fantasia}</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">{cliente.segmento || '—'}</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-[var(--moss-soft)] px-2 py-0.5 text-xs font-bold text-[var(--moss)]">
                      {STATUS_LABEL[cliente.status] || cliente.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">{cliente.contato_nome || cliente.email || '—'}</td>
                  <td className="px-4 py-3 text-right font-bold">{formatarBRL(cliente.fee_mensal)}</td>
                  {podeGerir ? (
                    <td className="px-4 py-3 text-right">
                      <AcoesLista
                        to={`/clientes/${cliente.id}`}
                        nome={cliente.nome_fantasia}
                        onExcluir={() => setAlvo(cliente)}
                      />
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
          titulo={`Excluir ${alvo.nome_fantasia}?`}
          texto="Só funciona se não houver tarefas ligadas a este cliente."
          confirmarLabel="Excluir cliente"
          processando={excluindo}
          onCancelar={() => setAlvo(null)}
          onConfirmar={onExcluir}
        />
      ) : null}
    </AppShell>
  )
}
