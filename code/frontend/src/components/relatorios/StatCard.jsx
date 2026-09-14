import { Link } from 'react-router-dom'

const TOM = {
  padrao: 'border-[var(--line)] bg-white',
  danger: 'border-[#f3c4c0] bg-[#fdecea]',
  warn: 'border-[#f0d4a8] bg-[#fff4e5]',
  action: 'border-[#f7c9a8] bg-[var(--orange-soft)]',
}

export default function StatCard({ valor, label, to, tom = 'padrao', testid }) {
  const classes = `block rounded-[12px] border px-4 py-3 ${TOM[tom] || TOM.padrao} ${
    to ? 'hover:brightness-[0.98]' : ''
  }`
  const corpo = (
    <>
      <div className="text-2xl font-extrabold tracking-tight text-[var(--moss)]">{valor}</div>
      <div className="mt-0.5 text-xs font-bold text-[var(--muted)]">{label}</div>
    </>
  )

  if (to) {
    return (
      <Link to={to} data-testid={testid} className={classes}>
        {corpo}
      </Link>
    )
  }

  return (
    <div data-testid={testid} className={classes}>
      {corpo}
    </div>
  )
}
