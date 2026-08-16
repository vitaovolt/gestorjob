import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteColaborador, listColaboradores } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import AcoesLista from '../components/ui/AcoesLista.jsx'
import ConfirmarExcluir from '../components/ui/ConfirmarExcluir.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatarBRL, papelLabel, temPermissao } from '../utils/format'

export default function ColaboradoresPage() {
  const { user } = useAuth()
  const podeEquipe = temPermissao(user, 'cadastrar_equipe')
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [pessoas, setPessoas] = useState([])
  const [erro, setErro] = useState('')
  const [alvo, setAlvo] = useState(null)
  const [excluindo, setExcluindo] = useState(false)

  useEffect(() => {
    listColaboradores()
      .then((payload) => setPessoas(payload.data || []))
      .catch(() => {
        setErro('Não foi possível carregar a equipe.')
        showToast('Não foi possível carregar os colaboradores. Suba a API em :8000.', 'erro')
      })
  }, [])

  async function onExcluir() {
    if (submittingRef.current || !alvo) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteColaborador(alvo.id)
      setPessoas((lista) => lista.filter((p) => p.id !== alvo.id))
      showToast('Colaborador removido')
      setAlvo(null)
    } catch (err) {
      const msg = err.response?.data?.message || 'Não foi possível excluir o colaborador.'
      showToast(msg, 'erro')
      setAlvo(null)
    } finally {
      submittingRef.current = false
      setExcluindo(false)
    }
  }

  return (
    <AppShell
      title="Colaboradores"
      cta={podeEquipe ? { label: '+ Colaborador', to: '/colaboradores/novo' } : undefined}
    >
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-3 text-sm text-[var(--muted)]">
        Custo/hora, carga e permissões.
        {podeEquipe ? (
          <>
            {' '}
            Use <strong>Editar</strong> ou <strong>Excluir</strong>.
          </>
        ) : null}
      </p>

      <div className="overflow-auto rounded-[12px] border border-[var(--line)] bg-white" data-testid="lista-colaboradores">
        <table className="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr className="border-b border-[var(--line)] bg-[var(--moss-soft)]/50 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
              <th className="px-4 py-3">Nome</th>
              <th className="px-4 py-3">Papel</th>
              <th className="px-4 py-3">Equipe</th>
              <th className="px-4 py-3 text-right">Custo/h</th>
              <th className="px-4 py-3">Carga</th>
              {podeEquipe ? <th className="px-4 py-3 text-right">Ações</th> : null}
            </tr>
          </thead>
          <tbody>
            {pessoas.length === 0 ? (
              <tr>
                <td colSpan={podeEquipe ? 6 : 5} className="px-4 py-10 text-center text-[var(--muted)]">
                  Ninguém na equipe ainda.
                  {podeEquipe ? ' Use + Colaborador.' : null}
                </td>
              </tr>
            ) : (
              pessoas.map((pessoa) => (
                <tr key={pessoa.id} className="border-b border-[var(--line)] last:border-0 hover:bg-[var(--moss-soft)]/40">
                  <td className="px-4 py-3">
                    {podeEquipe ? (
                      <Link
                        to={`/colaboradores/${pessoa.id}`}
                        className="font-extrabold text-[var(--moss)] hover:text-[var(--orange)]"
                      >
                        {pessoa.name}
                      </Link>
                    ) : (
                      <span className="font-extrabold text-[var(--moss)]">{pessoa.name}</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-[var(--muted)]">{papelLabel(pessoa.papel)}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">{pessoa.departamento || '—'}</td>
                  <td className="px-4 py-3 text-right font-bold">{formatarBRL(pessoa.custo_hora)}</td>
                  <td className="px-4 py-3 text-[var(--muted)]">
                    {pessoa.carga_semanal_horas ? `${pessoa.carga_semanal_horas} h` : '—'}
                  </td>
                  {podeEquipe ? (
                    <td className="px-4 py-3 text-right">
                      <AcoesLista
                        to={`/colaboradores/${pessoa.id}`}
                        nome={pessoa.name}
                        mostrarExcluir={pessoa.id !== user?.id}
                        onExcluir={() => setAlvo(pessoa)}
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
          titulo={`Excluir ${alvo.name}?`}
          texto="Só funciona se a pessoa não tiver tarefas nem horas apontadas."
          confirmarLabel="Excluir colaborador"
          processando={excluindo}
          onCancelar={() => setAlvo(null)}
          onConfirmar={onExcluir}
        />
      ) : null}
    </AppShell>
  )
}
