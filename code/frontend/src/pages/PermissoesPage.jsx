import { useEffect, useState } from 'react'
import { getPermissoes } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useToast } from '../context/ToastContext'
import { papelLabel } from '../utils/format'

function celulaTexto(celula) {
  if (!celula) return '—'
  if (celula.tipo === 'sim') return 'Sim'
  if (celula.tipo === 'nao') return 'Não'
  if (celula.tipo === 'parcial') return 'Parcial'
  if (celula.tipo === 'traco') return '—'
  if (celula.tipo === 'config') return celula.valor ? 'Config. (Sim)' : 'Config. (Não)'
  return '—'
}

export default function PermissoesPage() {
  const { showToast } = useToast()
  const [matriz, setMatriz] = useState({ papeis: [], linhas: [] })
  const [erro, setErro] = useState('')

  useEffect(() => {
    getPermissoes()
      .then((payload) => setMatriz(payload.data || { papeis: [], linhas: [] }))
      .catch(() => {
        setErro('Não foi possível carregar as permissões.')
        showToast('Não foi possível carregar as permissões. Suba a API em :8000.', 'erro')
      })
  }, [])

  const papeis = matriz.papeis || []

  return (
    <AppShell title="Permissões">
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Matriz por papel. Células <strong>Config.</strong> seguem o que está em Configurações.
      </p>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="matriz-permissoes">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Permissão</th>
              {papeis.map((papel) => (
                <th key={papel} className="px-4 py-3 text-center">
                  {papelLabel(papel)}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {(matriz.linhas || []).length === 0 ? (
              <tr>
                <td colSpan={papeis.length + 1} className="px-4 py-10 text-center text-[var(--muted)]">
                  Carregando matriz…
                </td>
              </tr>
            ) : (
              matriz.linhas.map((linha) => (
                <tr key={linha.id} className="border-b border-[var(--line)] last:border-0">
                  <td className="px-4 py-3 font-bold">{linha.label}</td>
                  {papeis.map((papel) => (
                    <td key={papel} className="px-4 py-3 text-center text-[var(--muted)]">
                      {celulaTexto(linha.celulas?.[papel])}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </AppShell>
  )
}
