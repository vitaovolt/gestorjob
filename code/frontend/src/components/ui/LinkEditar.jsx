import { Link } from 'react-router-dom'

/** Ação secundária na lista CRUD — visível, não compete com o CTA + Novo. */
export default function LinkEditar({ to, nome }) {
  return (
    <Link
      to={to}
      className="inline-flex rounded-md border border-[var(--line)] px-2.5 py-1 text-xs font-extrabold text-[var(--moss)] hover:border-[var(--orange)] hover:text-[var(--orange)]"
      aria-label={nome ? `Editar ${nome}` : 'Editar'}
    >
      Editar
    </Link>
  )
}
