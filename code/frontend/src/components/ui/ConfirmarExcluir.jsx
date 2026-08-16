export default function ConfirmarExcluir({
  titulo,
  texto,
  confirmarLabel = 'Excluir',
  testId = 'confirmar-excluir',
  processando,
  onCancelar,
  onConfirmar,
}) {
  return (
    <div className="fixed inset-0 z-[60] grid place-items-center bg-black/30 p-4">
      <div className="w-full max-w-sm rounded-[12px] border border-[var(--line)] bg-white p-5">
        <p className="m-0 font-extrabold text-[var(--moss)]">{titulo}</p>
        <p className="mt-2 mb-0 text-sm text-[var(--muted)]">{texto}</p>
        <div className="mt-4 flex justify-end gap-2">
          <button
            type="button"
            disabled={processando}
            onClick={onCancelar}
            className="rounded-lg border border-[var(--line)] px-3 py-2 text-sm font-bold"
          >
            Cancelar
          </button>
          <button
            type="button"
            data-testid={testId}
            disabled={processando}
            onClick={onConfirmar}
            className="rounded-lg bg-[#b42318] px-3 py-2 text-sm font-extrabold text-white disabled:opacity-70"
          >
            {processando ? 'Processando…' : confirmarLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
