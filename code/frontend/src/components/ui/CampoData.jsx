import { useRef } from 'react'
import { dateBRToISO, isoToDateBR, maskDateBR } from '../../utils/masks'

export default function CampoData({ value, onChange, className = '', testId }) {
  const pickerRef = useRef(null)
  const iso = dateBRToISO(value) || ''

  function abrirCalendario() {
    const el = pickerRef.current
    if (!el) return
    if (typeof el.showPicker === 'function') {
      try {
        el.showPicker()
        return
      } catch {
        // fallback: clique no input date
      }
    }
    el.click()
  }

  return (
    <div className="relative">
      <input
        value={value}
        onChange={(e) => onChange(maskDateBR(e.target.value))}
        className={`${className} pr-10`}
        inputMode="numeric"
        placeholder="DD/MM/AAAA"
        autoComplete="off"
        data-testid={testId}
      />
      <input
        ref={pickerRef}
        type="date"
        value={iso}
        onChange={(e) => onChange(e.target.value ? isoToDateBR(e.target.value) : '')}
        tabIndex={-1}
        aria-label="Escolher no calendário"
        data-testid={testId ? `${testId}-cal` : undefined}
        className="absolute top-1 right-1 h-8 w-8 cursor-pointer opacity-0"
      />
      <button
        type="button"
        tabIndex={-1}
        onClick={abrirCalendario}
        aria-hidden="true"
        className="pointer-events-none absolute top-1/2 right-1.5 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-[var(--moss)]"
      >
        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
          <rect x="3" y="5" width="18" height="16" rx="2" />
          <path d="M3 10h18M8 3v4M16 3v4" />
        </svg>
      </button>
    </div>
  )
}
