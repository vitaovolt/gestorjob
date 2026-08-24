import { useRef } from 'react'
import { ANEXO_ACCEPT } from '../../api/dominio'
import { formatarBytes } from '../../utils/format'

export default function CampoArquivo({
  files = [],
  onEscolher,
  onRemover,
  multiple = true,
  disabled = false,
  testId = 'anexo-arquivo',
  botaoTestId,
  botao = 'Escolher arquivos',
  hint = 'PDF, JPG, PNG, WEBP, GIF, Word ou Excel · até 10 MB',
}) {
  const inputRef = useRef(null)

  function onChange(event) {
    const lista = Array.from(event.target.files || [])
    event.target.value = ''
    if (lista.length) onEscolher(lista)
  }

  return (
    <div>
      <input
        ref={inputRef}
        type="file"
        accept={ANEXO_ACCEPT}
        multiple={multiple}
        disabled={disabled}
        className="sr-only"
        data-testid={testId}
        onChange={onChange}
      />
      <button
        type="button"
        disabled={disabled}
        onClick={() => inputRef.current?.click()}
        className="mt-1 flex w-full flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-[var(--line)] bg-[var(--moss-soft)]/30 px-4 py-6 text-center hover:border-[var(--moss)] hover:bg-[var(--moss-soft)]/50 disabled:opacity-60"
      >
        <svg viewBox="0 0 24 24" className="h-8 w-8 text-[var(--moss)]" fill="none" stroke="currentColor" strokeWidth="1.8">
          <path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49l9.9-9.9a4 4 0 0 1 5.66 5.66l-9.9 9.9a2 2 0 0 1-2.83-2.83l8.49-8.48" />
        </svg>
        <span
          className="rounded-lg bg-[var(--orange)] px-3 py-1.5 text-sm font-extrabold text-white"
          data-testid={botaoTestId || (testId ? `${testId}-botao` : undefined)}
        >
          {botao}
        </span>
        <span className="text-xs font-medium text-[var(--muted)]">{hint}</span>
      </button>
      {files.length ? (
        <ul className="mt-2 m-0 list-none p-0">
          {files.map((arquivo, indice) => (
            <li
              key={`${arquivo.name}-${arquivo.size}-${indice}`}
              className="flex items-center gap-2 border-b border-[var(--line)] py-2 last:border-0"
            >
              <span className="min-w-0 flex-1 truncate text-sm font-bold text-[var(--ink)]">
                {arquivo.name}
                <span className="ml-2 font-medium text-[var(--muted)]">{formatarBytes(arquivo.size)}</span>
              </span>
              {onRemover ? (
                <button
                  type="button"
                  disabled={disabled}
                  onClick={() => onRemover(indice)}
                  className="rounded-lg border border-[var(--line)] px-2 py-1 text-xs font-bold text-[var(--moss)]"
                >
                  Remover
                </button>
              ) : null}
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  )
}
