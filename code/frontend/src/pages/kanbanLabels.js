export const COLUNAS = [
  { id: 'a_fazer', label: 'A fazer' },
  { id: 'execucao', label: 'Em execução' },
  { id: 'revisao', label: 'Em revisão' },
  { id: 'cliente', label: 'Aguardando cliente' },
  { id: 'aprovado', label: 'Aprovado' },
  { id: 'concluido', label: 'Concluído' },
]

export const FASES_TIMER = [
  { id: 'analise', label: 'Análise' },
  { id: 'producao', label: 'Produção' },
  { id: 'revisao', label: 'Revisão' },
  { id: 'correcao', label: 'Correção' },
]

export const PRIORIDADE_LABEL = {
  urgente: 'Urgente',
  alta: 'Alta',
  media: 'Média',
  baixa: 'Baixa',
}

export function formatarTimer(segundos) {
  const s = Math.max(0, Math.floor(segundos || 0))
  const h = String(Math.floor(s / 3600)).padStart(2, '0')
  const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0')
  const sec = String(s % 60).padStart(2, '0')
  return `${h}:${m}:${sec}`
}

export function segundosAbertos(timerAberto, agora = Date.now()) {
  if (!timerAberto?.iniciado_em) return 0
  const inicio = new Date(timerAberto.iniciado_em).getTime()
  return Math.max(0, Math.floor((agora - inicio) / 1000))
}
