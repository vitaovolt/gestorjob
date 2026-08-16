import { createContext, useCallback, useContext, useState } from 'react'
import { createPortal } from 'react-dom'

const ToastContext = createContext(null)

export function ToastProvider({ children }) {
  const [toast, setToast] = useState(null)

  const dismissToast = useCallback(() => setToast(null), [])

  const showToast = useCallback((message, type = 'ok') => {
    setToast({ message, type })
    window.clearTimeout(showToast._t)
    showToast._t = window.setTimeout(() => setToast(null), 4200)
  }, [])

  return (
    <ToastContext.Provider value={{ toast, showToast, dismissToast }}>
      {children}
      <ToastBanner />
    </ToastContext.Provider>
  )
}

function ToastBanner() {
  const { toast, dismissToast } = useToast()
  if (!toast || typeof document === 'undefined') return null

  const erro = toast.type === 'erro'

  return createPortal(
    <div className="pointer-events-none fixed inset-x-0 top-16 z-[80] flex justify-center px-4">
      <div
        role="status"
        data-testid="toast"
        className={`pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-[0_12px_32px_rgba(26,34,32,0.16)] ${
          erro
            ? 'border-[#f3c4c0] bg-[#fdecea] text-[#9b1c1c]'
            : 'border-[#d5e4df] bg-[#f4f8f6] text-[var(--moss)]'
        }`}
      >
        <p className="m-0 min-w-0 flex-1 font-semibold leading-snug">{toast.message}</p>
        <button
          type="button"
          onClick={dismissToast}
          className="shrink-0 rounded-md px-1.5 text-lg leading-none text-[var(--muted)] hover:bg-black/5"
          aria-label="Fechar aviso"
        >
          ×
        </button>
      </div>
    </div>,
    document.body,
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast deve ser usado dentro de ToastProvider')
  return ctx
}
