import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  listNotificacoes,
  marcarNotificacaoLida,
  marcarTodasNotificacoesLidas,
  totalNaoLidas,
} from '../../api/dominio'
import { useToast } from '../../context/ToastContext'

export default function NotificacoesBell() {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const actingRef = useRef(false)
  const rootRef = useRef(null)
  const [aberto, setAberto] = useState(false)
  const [itens, setItens] = useState([])
  const [naoLidas, setNaoLidas] = useState(0)
  const [busy, setBusy] = useState('')

  async function carregar() {
    try {
      const [lista, contagem] = await Promise.all([listNotificacoes(), totalNaoLidas()])
      setItens(Array.isArray(lista.data) ? lista.data : [])
      setNaoLidas(Number(contagem.data?.total || 0))
    } catch {
      // silencioso no poll
    }
  }

  useEffect(() => {
    carregar()
    const id = window.setInterval(carregar, 60_000)
    return () => window.clearInterval(id)
  }, [])

  useEffect(() => {
    if (!aberto) return undefined
    function fechar(event) {
      if (rootRef.current && !rootRef.current.contains(event.target)) {
        setAberto(false)
      }
    }
    document.addEventListener('mousedown', fechar)
    return () => document.removeEventListener('mousedown', fechar)
  }, [aberto])

  async function onAbrir() {
    const next = !aberto
    setAberto(next)
    if (next) await carregar()
  }

  async function onLer(item) {
    if (actingRef.current) return
    actingRef.current = true
    setBusy(`lida-${item.id}`)
    try {
      if (!item.lida) {
        await marcarNotificacaoLida(item.id)
        setNaoLidas((n) => Math.max(0, n - 1))
        setItens((lista) =>
          lista.map((n) => (n.id === item.id ? { ...n, lida: true } : n)),
        )
      }
      setAberto(false)
      if (item.dados?.tarefa_id) {
        navigate('/')
      }
    } catch {
      showToast('Não foi possível abrir a notificação.', 'erro')
    } finally {
      actingRef.current = false
      setBusy('')
    }
  }

  async function onLerTodas() {
    if (actingRef.current || naoLidas === 0) return
    actingRef.current = true
    setBusy('todas')
    try {
      await marcarTodasNotificacoesLidas()
      setNaoLidas(0)
      setItens((lista) => lista.map((n) => ({ ...n, lida: true })))
      showToast('Todas marcadas como lidas')
    } catch {
      showToast('Não foi possível marcar como lidas.', 'erro')
    } finally {
      actingRef.current = false
      setBusy('')
    }
  }

  return (
    <div className="relative" ref={rootRef} data-testid="notif-root">
      <button
        type="button"
        data-testid="btn-notif"
        title="Notificações"
        onClick={onAbrir}
        className="relative grid h-9 w-9 place-items-center rounded-lg border border-[var(--line)] text-[var(--moss)] hover:bg-[var(--moss-soft)]"
        aria-expanded={aberto}
        aria-label={naoLidas > 0 ? `Notificações, ${naoLidas} não lidas` : 'Notificações'}
      >
        <span aria-hidden="true">🔔</span>
        {naoLidas > 0 ? (
          <span
            data-testid="notif-badge"
            className="absolute -right-1 -top-1 min-w-[1.1rem] rounded-full bg-[var(--orange)] px-1 text-[10px] font-extrabold text-white"
          >
            {naoLidas > 9 ? '9+' : naoLidas}
          </span>
        ) : null}
      </button>

      {aberto ? (
        <div
          data-testid="notif-panel"
          className="absolute right-0 z-40 mt-2 w-[320px] overflow-hidden rounded-[10px] border border-[var(--line)] bg-[var(--surface)] shadow-[0_12px_32px_rgba(26,34,32,0.14)]"
        >
          <div className="flex items-center gap-2 border-b border-[var(--line)] px-3 py-2">
            <strong className="text-sm text-[var(--moss)]">Notificações</strong>
            <span className="flex-1" />
            <button
              type="button"
              data-testid="notif-ler-todas"
              disabled={naoLidas === 0 || Boolean(busy)}
              onClick={onLerTodas}
              className="text-xs font-bold text-[var(--moss)] disabled:opacity-40"
            >
              {busy === 'todas' ? 'Processando…' : 'Marcar todas'}
            </button>
          </div>
          <div className="max-h-[360px] overflow-auto">
            {itens.length === 0 ? (
              <p className="m-0 px-3 py-6 text-center text-sm text-[var(--muted)]">Nenhuma notificação.</p>
            ) : (
              itens.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  data-testid={`notif-item-${item.id}`}
                  onClick={() => onLer(item)}
                  disabled={Boolean(busy)}
                  className={`block w-full border-b border-[var(--line)] px-3 py-2.5 text-left last:border-0 hover:bg-[var(--moss-soft)]/40 ${
                    item.lida ? 'opacity-70' : ''
                  }`}
                >
                  <span className="block text-sm font-bold text-[var(--moss)]">{item.titulo}</span>
                  {item.corpo ? (
                    <span className="mt-0.5 block text-xs text-[var(--muted)]">{item.corpo}</span>
                  ) : null}
                </button>
              ))
            )}
          </div>
        </div>
      ) : null}
    </div>
  )
}
