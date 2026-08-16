import { useEffect, useRef, useState } from 'react'
import {
  ANEXO_ACCEPT,
  atualizarChecklist,
  criarComentario,
  deleteAnexo,
  deleteTarefa,
  downloadAnexo,
  iniciarTimer,
  mensagemErroUploadAnexo,
  pausarTimer,
  updateTarefa,
  uploadAnexo,
  validarAnexoCliente,
} from '../../api/dominio'
import { useAuth } from '../../context/AuthContext'
import { useToast } from '../../context/ToastContext'
import { formatarBRL, formatarBytes, temPermissao } from '../../utils/format'
import ConfirmarExcluir from '../ui/ConfirmarExcluir.jsx'
import { COLUNAS, FASES_TIMER, formatarTimer, PRIORIDADE_LABEL, segundosAbertos } from '../../pages/kanbanLabels'

export default function TaskDrawer({ tarefa, onClose, onAtualizada, onExcluida }) {
  const { user } = useAuth()
  const { showToast } = useToast()
  const actingRef = useRef(false)
  const [busy, setBusy] = useState('')
  const [confirmarExcluir, setConfirmarExcluir] = useState(false)
  const [alvoAnexo, setAlvoAnexo] = useState(null)
  const [comentario, setComentario] = useState('')
  const fileRef = useRef(null)
  const podeExcluir = temPermissao(user, 'excluir_tarefas')
  const podeAnexar = temPermissao(user, 'anexar')
  const podeComentar = temPermissao(user, 'comentar')
  const verFinanceiro = temPermissao(user, 'ver_financeiro')
  const aberto = Boolean(tarefa?.timer_aberto?.iniciado_em)
  const timer = tarefa?.timer_aberto
  const [now, setNow] = useState(() => Date.now())

  useEffect(() => {
    if (!aberto) return undefined
    const id = window.setInterval(() => setNow(Date.now()), 1000)
    return () => window.clearInterval(id)
  }, [aberto, timer?.id])

  if (!tarefa) return null

  const faseAtual = timer?.fase || tarefa.fase_timer
  const baseSeg = Number(tarefa.segundos_fase) || 0
  const displaySeg = aberto ? baseSeg + segundosAbertos(timer, now) : baseSeg
  const labelFase = FASES_TIMER.find((f) => f.id === faseAtual)?.label || ''
  const statusTimer = aberto ? `Rodando · ${labelFase}` : faseAtual ? `Pausado · ${labelFase}` : 'Parado'
  const comentarios = tarefa.comentarios || []

  async function run(chave, fn, okMsg) {
    if (actingRef.current) return
    actingRef.current = true
    setBusy(chave)
    try {
      const payload = await fn()
      onAtualizada(payload.data)
      showToast(okMsg)
    } catch (err) {
      actingRef.current = false
      setBusy('')
      const raw =
        err.response?.data?.errors?.fase?.[0] ||
        err.response?.data?.message ||
        ''
      const msg =
        raw && !String(raw).includes('SQLSTATE')
          ? raw
          : 'Não deu certo. Tente de novo.'
      showToast(msg, 'erro')
      return
    }
    actingRef.current = false
    setBusy('')
  }

  function onFase(fase) {
    run(`fase-${fase}`, () => iniciarTimer(tarefa.id, fase), 'Timer em andamento')
  }

  function onPlay() {
    if (!faseAtual) return
    run('play', () => iniciarTimer(tarefa.id, faseAtual), 'Timer em andamento')
  }

  function onPausar() {
    run('pausar', () => pausarTimer(tarefa.id), 'Timer pausado')
  }

  function onMover(status) {
    run(`move-${status}`, () => updateTarefa(tarefa.id, { status }), 'Tarefa movida')
  }

  function onCheck(item, feito) {
    run(`check-${item.id}`, () => atualizarChecklist(tarefa.id, item.id, feito), 'Checklist atualizado')
  }

  async function onExcluir() {
    if (actingRef.current || !tarefa) return
    actingRef.current = true
    setBusy('excluir')
    try {
      await deleteTarefa(tarefa.id)
      showToast('Tarefa excluída')
      onExcluida(tarefa.id)
    } catch (err) {
      actingRef.current = false
      setBusy('')
      setConfirmarExcluir(false)
      const msg = err.response?.data?.message || 'Não foi possível excluir.'
      showToast(msg, 'erro')
      return
    }
    actingRef.current = false
    setBusy('')
  }

  async function onEnviarAnexo(event) {
    const arquivo = event.target.files?.[0]
    event.target.value = ''
    if (!arquivo) return
    const erroLocal = validarAnexoCliente(arquivo)
    if (erroLocal) {
      showToast(erroLocal, 'erro')
      return
    }
    if (actingRef.current) return
    actingRef.current = true
    setBusy('anexo')
    try {
      const payload = await uploadAnexo(tarefa.id, arquivo)
      onAtualizada(payload.data)
      showToast('Arquivo anexado')
    } catch (err) {
      showToast(mensagemErroUploadAnexo(err), 'erro')
    }
    actingRef.current = false
    setBusy('')
  }

  async function onBaixarAnexo(anexo) {
    if (actingRef.current) return
    actingRef.current = true
    setBusy(`baixar-${anexo.id}`)
    try {
      await downloadAnexo(tarefa.id, anexo.id, anexo.nome)
    } catch {
      showToast('Não foi possível baixar.', 'erro')
    }
    actingRef.current = false
    setBusy('')
  }

  async function onExcluirAnexo() {
    if (actingRef.current || !alvoAnexo) return
    actingRef.current = true
    setBusy('excluir-anexo')
    try {
      const payload = await deleteAnexo(tarefa.id, alvoAnexo.id)
      onAtualizada(payload.data)
      showToast('Anexo removido')
      setAlvoAnexo(null)
      actingRef.current = false
      setBusy('')
    } catch (err) {
      actingRef.current = false
      setBusy('')
      setAlvoAnexo(null)
      const msg = err.response?.data?.message || 'Não foi possível excluir o anexo.'
      showToast(msg, 'erro')
    }
  }

  async function onComentar(event) {
    event.preventDefault()
    const texto = comentario.trim()
    if (!texto || actingRef.current) return
    actingRef.current = true
    setBusy('comentario')
    try {
      const payload = await criarComentario(tarefa.id, texto)
      onAtualizada(payload.data)
      setComentario('')
      showToast('Comentário publicado')
    } catch (err) {
      showToast(err.response?.data?.message || 'Não foi possível comentar.', 'erro')
    }
    actingRef.current = false
    setBusy('')
  }

  const itens = tarefa.checklist_itens || []
  const feitos = itens.filter((i) => i.feito).length

  return (
    <div className="fixed inset-0 z-40 flex justify-end bg-black/25" data-testid="drawer-root">
      <button type="button" className="h-full flex-1 cursor-default" aria-label="Fechar drawer" onClick={onClose} />
      <aside className="flex h-full w-full max-w-xl flex-col bg-[var(--surface)] shadow-[-12px_0_40px_rgba(26,34,32,0.18)]">
        <header className="flex items-center gap-3 border-b border-[var(--line)] px-4 py-3">
          <strong className="min-w-0 flex-1 text-[var(--moss)]">{tarefa.titulo}</strong>
          {aberto ? (
            <span data-testid="timer-pill" className="rounded-full bg-[var(--orange)] px-3 py-1 text-xs font-extrabold text-white">
              {formatarTimer(displaySeg)} {FASES_TIMER.find((f) => f.id === faseAtual)?.label || faseAtual}
            </span>
          ) : null}
          <button type="button" onClick={onClose} className="grid h-8 w-8 place-items-center rounded-lg border border-[var(--line)]">
            ✕
          </button>
        </header>

        <div className="grid min-h-0 flex-1 gap-4 overflow-auto p-4 md:grid-cols-2">
          <section>
            <h2 className="m-0 text-sm font-extrabold text-[var(--moss)]">Execução</h2>
            <div className="mt-2 flex flex-wrap gap-2">
              <span className="rounded-full bg-[var(--moss-soft)] px-2 py-1 text-xs font-bold text-[var(--moss)]">
                {COLUNAS.find((c) => c.id === tarefa.status)?.label}
              </span>
              <span className="rounded-full bg-[var(--orange-soft)] px-2 py-1 text-xs font-bold text-[var(--orange)]">
                {PRIORIDADE_LABEL[tarefa.prioridade] || tarefa.prioridade}
              </span>
              {tarefa.atrasada ? (
                <span className="rounded-full bg-[#fdecea] px-2 py-1 text-xs font-bold text-[#b42318]">Atrasado</span>
              ) : null}
              {tarefa.recorrente ? (
                <span className="rounded-full bg-[var(--moss-soft)] px-2 py-1 text-xs font-bold text-[var(--moss)]">Recorrente</span>
              ) : null}
            </div>

            <p className="mt-4 mb-2 text-xs font-bold text-[var(--muted)]">Timer</p>
            <div
              data-testid="timer-display"
              className={`mb-3 rounded-[12px] border-2 p-3 ${
                aberto
                  ? 'border-[var(--orange)] bg-[var(--orange-soft)]'
                  : 'border-[var(--line)] bg-white'
              }`}
            >
              <p className="m-0 text-[0.7rem] font-extrabold tracking-wide uppercase text-[var(--muted)]">
                {statusTimer}
              </p>
              <div className="mt-1 flex items-center gap-3">
                <p className="m-0 flex-1 text-3xl font-extrabold tabular-nums text-[var(--moss)]">
                  {formatarTimer(displaySeg)}
                </p>
                {aberto ? (
                  <button
                    type="button"
                    data-testid="timer-pausar"
                    disabled={Boolean(busy)}
                    onClick={onPausar}
                    aria-label="Pausar timer"
                    className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[var(--moss)] text-white hover:brightness-110 disabled:opacity-50"
                  >
                    {busy === 'pausar' ? (
                      <span className="text-[0.6rem] font-extrabold">…</span>
                    ) : (
                      <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                        <rect x="6" y="5" width="4" height="14" rx="1" fill="currentColor" />
                        <rect x="14" y="5" width="4" height="14" rx="1" fill="currentColor" />
                      </svg>
                    )}
                  </button>
                ) : (
                  <button
                    type="button"
                    data-testid="timer-play"
                    disabled={!faseAtual || Boolean(busy)}
                    onClick={onPlay}
                    aria-label="Retomar timer"
                    className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[var(--orange)] text-white hover:brightness-110 disabled:opacity-40"
                  >
                    {busy === 'play' ? (
                      <span className="text-[0.6rem] font-extrabold">…</span>
                    ) : (
                      <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                        <path d="M8 5v14l11-7z" fill="currentColor" />
                      </svg>
                    )}
                  </button>
                )}
              </div>
              <p className="mt-1 mb-0 text-xs text-[var(--muted)]">
                {aberto
                  ? 'O tempo está sendo apontado nesta fase.'
                  : faseAtual
                    ? 'Play retoma esta fase. Outra fase começa do zero.'
                    : 'Clique numa fase abaixo para começar a contar.'}
              </p>
            </div>
            <p className="mt-2 mb-2 text-xs font-bold text-[var(--muted)]">Fase do timer</p>
            <div className="flex flex-wrap gap-2">
              {FASES_TIMER.map((fase) => (
                <button
                  key={fase.id}
                  type="button"
                  data-testid={`fase-${fase.id}`}
                  disabled={Boolean(busy)}
                  onClick={() => onFase(fase.id)}
                  className={`rounded-lg px-3 py-1.5 text-xs font-extrabold disabled:opacity-70 ${
                    faseAtual === fase.id && aberto
                      ? 'bg-[var(--orange)] text-white'
                      : faseAtual === fase.id
                        ? 'border-2 border-[var(--orange)] bg-white text-[var(--orange)]'
                        : 'border border-[var(--line)] bg-white text-[var(--moss)]'
                  }`}
                >
                  {busy === `fase-${fase.id}` ? 'Processando…' : fase.label}
                </button>
              ))}
            </div>

            <div className="mt-4 rounded-[10px] border border-[var(--line)] p-3">
              <p className="m-0 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
                Checklist {feitos}/{itens.length}
              </p>
              <ul className="mt-2 m-0 list-none p-0">
                {itens.map((item) => (
                  <li key={item.id} className="py-1">
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={Boolean(item.feito)}
                        disabled={Boolean(busy)}
                        onChange={(e) => onCheck(item, e.target.checked)}
                      />
                      {item.titulo}
                    </label>
                  </li>
                ))}
              </ul>
            </div>

            {tarefa.briefing ? (
              <div className="mt-4 rounded-[10px] border border-[var(--line)] p-3 text-sm text-[var(--muted)]">
                <p className="m-0 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">Briefing</p>
                <p className="mt-2 mb-0">{tarefa.briefing}</p>
              </div>
            ) : null}

            <div className="mt-4 rounded-[10px] border border-[var(--line)] p-3" data-testid="timeline-comentarios">
              <p className="m-0 text-xs font-extrabold tracking-wide uppercase text-[var(--muted)]">
                Comentários / timeline
              </p>
              <ul className="mt-2 m-0 max-h-48 list-none space-y-2 overflow-auto p-0">
                {comentarios.length === 0 ? (
                  <li className="text-sm text-[var(--muted)]">Nada por aqui ainda.</li>
                ) : (
                  comentarios.map((item) => (
                    <li key={item.id} className="text-sm">
                      <strong className="text-[var(--moss)]">{item.autor || '—'}</strong>
                      <span className="text-[var(--muted)]"> · {item.corpo}</span>
                    </li>
                  ))
                )}
              </ul>
              {podeComentar ? (
                <form onSubmit={onComentar} className="mt-3 flex gap-2">
                  <input
                    value={comentario}
                    onChange={(e) => setComentario(e.target.value)}
                    data-testid="comentario-input"
                    placeholder="Escrever comentário…"
                    className="min-w-0 flex-1 rounded-lg border border-[var(--line)] px-3 py-2 text-sm"
                    disabled={Boolean(busy)}
                  />
                  <button
                    type="submit"
                    data-testid="comentario-enviar"
                    disabled={Boolean(busy) || !comentario.trim()}
                    className="rounded-lg bg-[var(--moss)] px-3 py-2 text-xs font-extrabold text-white disabled:opacity-50"
                  >
                    {busy === 'comentario' ? '…' : 'Enviar'}
                  </button>
                </form>
              ) : null}
            </div>
          </section>

          <section>
            <h2 className="m-0 text-sm font-extrabold text-[var(--moss)]">Contexto</h2>
            <div className="mt-2 rounded-[10px] border border-[var(--line)] p-3 text-sm">
              <p>Cliente: <strong>{tarefa.cliente?.nome_fantasia || '—'}</strong></p>
              <p>Serviço: {tarefa.servico?.nome || '—'}</p>
            </div>

            {verFinanceiro ? (
              <div
                className="mt-4 rounded-[10px] border border-[var(--orange)] bg-[var(--orange-soft)] p-3"
                data-testid="custo-acumulado"
              >
                <p className="m-0 text-[0.7rem] font-extrabold tracking-wide text-[var(--moss)]">CUSTO ACUMULADO</p>
                <p className="mt-1 mb-0 text-2xl font-extrabold text-[var(--moss)]">
                  {formatarBRL(tarefa.custo_acumulado ?? 0)}
                </p>
                <p className="mt-1 mb-0 text-sm text-[var(--muted)]">
                  {Number(tarefa.horas_acumuladas ?? 0).toLocaleString('pt-BR')} h apontadas
                </p>
              </div>
            ) : (
              <div className="mt-4 rounded-[10px] border border-[var(--line)] p-3 text-sm text-[var(--muted)]">
                Custo oculto para este perfil.
              </div>
            )}

            <p className="mt-4 mb-2 text-xs font-bold text-[var(--muted)]">Anexos</p>
            <div className="rounded-[10px] border border-[var(--line)] p-3" data-testid="lista-anexos">
              {(tarefa.anexos || []).length === 0 ? (
                <p className="m-0 text-sm text-[var(--muted)]">Nenhum arquivo ainda.</p>
              ) : (
                <ul className="m-0 list-none p-0">
                  {(tarefa.anexos || []).map((anexo) => (
                    <li key={anexo.id} className="flex items-center gap-2 border-b border-[var(--line)] py-2 last:border-0">
                      <span className="min-w-0 flex-1">
                        <strong className="block truncate text-sm text-[var(--ink)]">{anexo.nome}</strong>
                        <span className="text-xs text-[var(--muted)]">
                          {formatarBytes(anexo.tamanho_bytes)}
                          {anexo.autor ? ` · ${anexo.autor}` : ''}
                        </span>
                      </span>
                      <button
                        type="button"
                        data-testid={`baixar-anexo-${anexo.id}`}
                        disabled={Boolean(busy)}
                        onClick={() => onBaixarAnexo(anexo)}
                        className="rounded-lg border border-[var(--line)] px-2 py-1 text-xs font-bold text-[var(--moss)] disabled:opacity-50"
                      >
                        {busy === `baixar-${anexo.id}` ? '…' : 'Baixar'}
                      </button>
                      {podeAnexar ? (
                        <button
                          type="button"
                          data-testid={`excluir-anexo-${anexo.id}`}
                          disabled={Boolean(busy)}
                          onClick={() => setAlvoAnexo(anexo)}
                          className="rounded-lg border border-[#f3c4c0] px-2 py-1 text-xs font-bold text-[#b42318] disabled:opacity-50"
                        >
                          Excluir
                        </button>
                      ) : null}
                    </li>
                  ))}
                </ul>
              )}
              {podeAnexar ? (
                <div className="mt-3">
                  <input
                    ref={fileRef}
                    type="file"
                    data-testid="anexo-arquivo"
                    className="absolute h-px w-px overflow-hidden opacity-0"
                    accept={ANEXO_ACCEPT}
                    disabled={Boolean(busy)}
                    onChange={onEnviarAnexo}
                  />
                  <button
                    type="button"
                    data-testid="enviar-anexo"
                    disabled={Boolean(busy)}
                    onClick={() => fileRef.current?.click()}
                    className="rounded-lg bg-[var(--orange)] px-3 py-2 text-xs font-extrabold text-white hover:brightness-110 disabled:opacity-70"
                  >
                    {busy === 'anexo' ? 'Processando…' : 'Enviar arquivo'}
                  </button>
                  <p className="mt-2 mb-0 text-[11px] text-[var(--muted)]">
                    Só PDF, JPG, PNG, WEBP, GIF, Word ou Excel · até 10 MB
                  </p>
                </div>
              ) : null}
            </div>
            <p className="mt-4 mb-2 text-xs font-bold text-[var(--muted)]">Mover status</p>
            <div className="flex flex-wrap gap-2">
              {COLUNAS.map((col) => (
                <button
                  key={col.id}
                  type="button"
                  data-testid={`mover-${col.id}`}
                  disabled={Boolean(busy) || tarefa.status === col.id}
                  onClick={() => onMover(col.id)}
                  className={`rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-50 ${
                    tarefa.status === col.id
                      ? 'bg-[var(--moss)] text-white'
                      : 'border border-[var(--line)] bg-white text-[var(--moss)]'
                  }`}
                >
                  {busy === `move-${col.id}` ? 'Processando…' : col.label}
                </button>
              ))}
            </div>
            {podeExcluir ? (
              <div className="mt-6">
                <button
                  type="button"
                  data-testid="excluir-tarefa"
                  disabled={Boolean(busy)}
                  onClick={() => setConfirmarExcluir(true)}
                  className="rounded-lg border border-[#f3c4c0] px-3 py-2 text-xs font-extrabold text-[#b42318] hover:bg-[#fdecea] disabled:opacity-50"
                >
                  Excluir tarefa
                </button>
              </div>
            ) : null}
          </section>
        </div>
      </aside>
      {confirmarExcluir ? (
        <ConfirmarExcluir
          titulo={`Excluir ${tarefa.titulo}?`}
          texto="Só funciona se a tarefa não tiver horas apontadas. O checklist some junto."
          confirmarLabel="Excluir tarefa"
          processando={busy === 'excluir'}
          onCancelar={() => setConfirmarExcluir(false)}
          onConfirmar={onExcluir}
        />
      ) : null}
      {alvoAnexo ? (
        <ConfirmarExcluir
          titulo={`Excluir ${alvoAnexo.nome}?`}
          texto="O arquivo sai da tarefa e do armazenamento."
          confirmarLabel="Excluir anexo"
          testId="confirmar-excluir-anexo"
          processando={busy === 'excluir-anexo'}
          onCancelar={() => setAlvoAnexo(null)}
          onConfirmar={onExcluirAnexo}
        />
      ) : null}
    </div>
  )
}
