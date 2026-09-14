export function tomCarga(percentual) {
  const n = Number(percentual)
  if (!Number.isFinite(n)) return 'ok'
  if (n > 90) return 'danger'
  if (n > 80) return 'warn'
  return 'ok'
}

const BARRA = {
  ok: 'bg-[var(--moss)]',
  action: 'bg-[var(--orange)]',
  warn: 'bg-[#b86a14]',
  danger: 'bg-[#b42318]',
}

export default function BarraCarga({ percentual, tom }) {
  const n = Number(percentual)
  const largura = Number.isFinite(n) ? Math.min(Math.max(n, 0), 100) : 0
  const cor = tom || tomCarga(n)

  return (
    <div className="h-2 overflow-hidden rounded-full bg-[#e6ebe8]">
      <i className={`block h-full rounded-full ${BARRA[cor] || BARRA.ok}`} style={{ width: `${largura}%` }} />
    </div>
  )
}
