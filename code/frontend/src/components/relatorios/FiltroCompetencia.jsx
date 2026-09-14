export default function FiltroCompetencia({ value, onChange, id = 'filtro-competencia' }) {
  return (
    <label className="flex items-center gap-2 text-xs font-extrabold text-[var(--muted)]">
      Competência
      <input
        id={id}
        data-testid="filtro-competencia"
        type="month"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="rounded-lg border border-[var(--line)] px-2 py-1.5 text-sm font-bold text-[var(--moss)]"
      />
    </label>
  )
}
