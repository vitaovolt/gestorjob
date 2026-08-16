import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listEmpresas } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import AcoesLista from '../components/ui/AcoesLista.jsx'
import { useToast } from '../context/ToastContext'

const PLANO = { starter: 'Starter', pro: 'Pro', enterprise: 'Enterprise' }
const STATUS = { ativo: 'Ativo', trial: 'Trial', suspenso: 'Suspenso' }

export default function EmpresasPage() {
  const { showToast } = useToast()
  const [empresas, setEmpresas] = useState([])
  const [erro, setErro] = useState('')

  useEffect(() => {
    listEmpresas()
      .then((payload) => setEmpresas(payload.data || []))
      .catch(() => {
        setErro('Não foi possível carregar as empresas.')
        showToast('Não foi possível carregar as empresas. Suba a API em :8000.', 'erro')
      })
  }, [])

  return (
    <AppShell title="Empresas" cta={{ label: '+ Empresa', to: '/empresas/novo' }}>
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Agências da plataforma. Criar envia convite para o admin definir a senha.
      </p>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="lista-empresas">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Empresa</th>
              <th className="px-4 py-3">Plano</th>
              <th className="px-4 py-3">Assentos</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Admin</th>
              <th className="px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            {empresas.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-[var(--muted)]">
                  Nenhuma agência ainda. Use + Empresa.
                </td>
              </tr>
            ) : (
              empresas.map((empresa) => (
                <tr key={empresa.id} className="border-b border-[var(--line)] last:border-0 hover:bg-[var(--moss-soft)]/40">
                  <td className="px-4 py-3">
                    <Link
                      to={`/empresas/${empresa.id}`}
                      className="font-extrabold text-[var(--moss)] hover:text-[var(--orange)]"
                    >
                      {empresa.nome}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">{PLANO[empresa.plano] || empresa.plano}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">
                    {empresa.usuarios_count}/{empresa.limite_usuarios}
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-[var(--moss-soft)] px-2 py-0.5 text-xs font-bold text-[var(--moss)]">
                      {STATUS[empresa.status] || empresa.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">
                    {empresa.admin?.email || '—'}
                    {empresa.admin?.convite_pendente ? (
                      <span className="ml-1 text-xs font-bold text-[var(--orange)]">pendente</span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <AcoesLista to={`/empresas/${empresa.id}`} nome={empresa.nome} mostrarExcluir={false} />
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
