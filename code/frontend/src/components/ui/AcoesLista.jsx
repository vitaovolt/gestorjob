import LinkEditar from './LinkEditar.jsx'

/** Ações da linha CRUD: Editar + Excluir visíveis (admin/gerente). */
export default function AcoesLista({ to, nome, onExcluir, mostrarExcluir = true }) {
  return (
    <div className="flex flex-wrap justify-end gap-1.5">
      <LinkEditar to={to} nome={nome} />
      {mostrarExcluir ? (
        <button
          type="button"
          onClick={onExcluir}
          className="inline-flex rounded-md border border-[#f3c4c0] px-2.5 py-1 text-xs font-extrabold text-[#9b1c1c] hover:bg-[#fdecea]"
          aria-label={nome ? `Excluir ${nome}` : 'Excluir'}
        >
          Excluir
        </button>
      ) : null}
    </div>
  )
}
