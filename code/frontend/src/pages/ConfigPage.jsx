import { useEffect, useRef, useState } from 'react'
import { getConfiguracao, updateConfiguracao } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

const VAZIO = {
  gerente_cria_usuarios: true,
  colaborador_cria_tarefas: false,
  gerente_exclui_tarefas: false,
  timer_ao_abrir: false,
  notif_email: true,
  notif_in_app: true,
  digest_diario: false,
  colaborador_so_alocadas: true,
}

function Check({ id, label, hint, checked, disabled, onChange }) {
  return (
    <label className={`flex items-start gap-2 text-sm ${disabled ? 'opacity-60' : ''}`}>
      <input
        type="checkbox"
        data-testid={`config-${id.replaceAll('_', '-')}`}
        className="mt-0.5"
        checked={checked}
        disabled={disabled}
        onChange={(e) => onChange(id, e.target.checked)}
      />
      <span>
        <span className="font-bold text-[var(--ink)]">{label}</span>
        {hint ? <span className="mt-0.5 block text-xs text-[var(--muted)]">{hint}</span> : null}
      </span>
    </label>
  )
}

export default function ConfigPage() {
  const { atualizarUsuario } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(VAZIO)
  const [editaveis, setEditaveis] = useState([])
  const [submitting, setSubmitting] = useState(false)
  const [erro, setErro] = useState('')

  useEffect(() => {
    getConfiguracao()
      .then((payload) => {
        setForm({ ...VAZIO, ...(payload.data?.config || {}) })
        setEditaveis(payload.data?.editaveis || [])
      })
      .catch(() => {
        setErro('Não foi possível carregar as configurações.')
        showToast('Não foi possível carregar as configurações. Suba a API em :8000.', 'erro')
      })
  }, [])

  function setFlag(chave, valor) {
    setForm((atual) => ({ ...atual, [chave]: valor }))
  }

  function pode(chave) {
    return editaveis.includes(chave) && !submitting
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    submittingRef.current = true
    setSubmitting(true)
    setErro('')
    try {
      const payload = await updateConfiguracao(form)
      setForm({ ...VAZIO, ...(payload.data?.config || {}) })
      setEditaveis(payload.data?.editaveis || editaveis)
      await atualizarUsuario()
      showToast('Configurações salvas')
      submittingRef.current = false
      setSubmitting(false)
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg = err.response?.data?.message || 'Não foi possível salvar as configurações.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  const card = 'rounded-[12px] border border-[var(--line)] bg-white'
  const head = 'border-b border-[var(--line)] px-4 py-3 text-sm font-extrabold text-[var(--moss)]'
  const body = 'flex flex-col gap-3 p-4'

  return (
    <AppShell title="Configurações">
      {erro ? <p className="mb-3 font-semibold text-[#b42318]">{erro}</p> : null}
      <p className="mt-0 mb-4 text-sm text-[var(--muted)]">
        Quatro blocos para o tenant. O que está marcado como Config. na matriz de permissões vem daqui.
      </p>

      <form onSubmit={onSubmit} data-testid="form-config">
        <div className="grid gap-4 md:grid-cols-2">
          <section className={card}>
            <h2 className={head}>Usuários</h2>
            <div className={body}>
              <Check
                id="gerente_cria_usuarios"
                label="Gerente pode cadastrar a equipe"
                hint="Convite, edição e exclusão de colaboradores."
                checked={form.gerente_cria_usuarios}
                disabled={!pode('gerente_cria_usuarios')}
                onChange={setFlag}
              />
            </div>
          </section>

          <section className={card}>
            <h2 className={head}>Tarefas</h2>
            <div className={body}>
              <Check
                id="colaborador_cria_tarefas"
                label="Colaborador pode criar tarefas"
                checked={form.colaborador_cria_tarefas}
                disabled={!pode('colaborador_cria_tarefas')}
                onChange={setFlag}
              />
              <Check
                id="gerente_exclui_tarefas"
                label="Gerente pode excluir tarefas"
                hint="Só funciona se a tarefa não tiver horas apontadas."
                checked={form.gerente_exclui_tarefas}
                disabled={!pode('gerente_exclui_tarefas')}
                onChange={setFlag}
              />
              <Check
                id="timer_ao_abrir"
                label="Iniciar timer ao abrir o card"
                hint="Guardado agora; o play do timer continua manual neste ciclo."
                checked={form.timer_ao_abrir}
                disabled={!pode('timer_ao_abrir')}
                onChange={setFlag}
              />
            </div>
          </section>

          <section className={card}>
            <h2 className={head}>Notificações</h2>
            <div className={body}>
              <Check
                id="notif_email"
                label="Avisos por e-mail"
                hint="Prazo hoje: e-mail para os responsáveis (comando diário 07:00)."
                checked={form.notif_email}
                disabled={!pode('notif_email')}
                onChange={setFlag}
              />
              <Check
                id="notif_in_app"
                label="Avisos no sistema"
                hint="Sino no topo: alocação, mudança de status e prazo hoje."
                checked={form.notif_in_app}
                disabled={!pode('notif_in_app')}
                onChange={setFlag}
              />
              <Check
                id="digest_diario"
                label="Digest diário"
                hint="Resumo do dia. Envio ainda não dispara."
                checked={form.digest_diario}
                disabled={!pode('digest_diario')}
                onChange={setFlag}
              />
            </div>
          </section>

          <section className={card}>
            <h2 className={head}>Visibilidade</h2>
            <div className={body}>
              <Check
                id="colaborador_so_alocadas"
                label="Colaborador vê só tarefas alocadas"
                hint="Vale também para visualizador. Admin e gerente continuam vendo o quadro inteiro."
                checked={form.colaborador_so_alocadas}
                disabled={!pode('colaborador_so_alocadas')}
                onChange={setFlag}
              />
            </div>
          </section>
        </div>

        <div className="mt-4">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-lg bg-[var(--orange)] px-4 py-2 text-sm font-extrabold text-white hover:brightness-110 disabled:opacity-70"
          >
            {submitting ? 'Processando…' : 'Salvar configurações'}
          </button>
        </div>
      </form>
    </AppShell>
  )
}
