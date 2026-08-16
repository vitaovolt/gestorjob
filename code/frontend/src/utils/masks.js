/** Máscaras e validação pt-BR — nascer junto com o campo. */

export function onlyDigits(value, maxLen) {
  const digits = String(value ?? '').replace(/\D/g, '')
  return typeof maxLen === 'number' ? digits.slice(0, maxLen) : digits
}

export function maskMoneyBR(raw) {
  const digits = onlyDigits(raw, 12)
  if (!digits) return ''
  return (Number(digits) / 100).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export function parseMoneyBR(masked) {
  if (!masked || !String(masked).trim()) return NaN
  const n = Number(
    String(masked)
      .replace(/\s/g, '')
      .replace(/R\$\s?/i, '')
      .replace(/\./g, '')
      .replace(',', '.'),
  )
  return Number.isFinite(n) ? n : NaN
}

export function moneyFromNumber(valor) {
  if (valor === null || valor === undefined || valor === '') return ''
  const n = Number(valor)
  if (!Number.isFinite(n)) return ''
  return maskMoneyBR(String(Math.round(n * 100)))
}

export function maskDateBR(raw) {
  const d = onlyDigits(raw, 8)
  if (d.length <= 2) return d
  if (d.length <= 4) return `${d.slice(0, 2)}/${d.slice(2)}`
  return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`
}

export function dateBRToISO(br) {
  const m = String(br || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
  if (!m) return null
  const dd = Number(m[1])
  const mm = Number(m[2])
  const yyyy = Number(m[3])
  const dt = new Date(yyyy, mm - 1, dd)
  if (dt.getFullYear() !== yyyy || dt.getMonth() !== mm - 1 || dt.getDate() !== dd) {
    return null
  }
  return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`
}

export function isoToDateBR(iso) {
  const s = String(iso || '').slice(0, 10)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return ''
  const [y, m, d] = s.split('-')
  return `${d}/${m}/${y}`
}

export function maskPhoneBR(raw) {
  const d = onlyDigits(raw, 11)
  if (!d) return ''
  if (d.length <= 2) return `(${d}`
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
}

export function maskCpf(raw) {
  const d = onlyDigits(raw, 11)
  if (d.length <= 3) return d
  if (d.length <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`
  if (d.length <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`
  return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`
}

/** CNPJ numérico ou alfanumérico (IN 2.229): AA.AAA.AAA/AAAA-DV — não apagar letras. */
export function maskCnpj(raw) {
  const d = String(raw || '')
    .toUpperCase()
    .replace(/[^0-9A-Z]/g, '')
    .slice(0, 14)
  if (d.length <= 2) return d
  if (d.length <= 5) return `${d.slice(0, 2)}.${d.slice(2)}`
  if (d.length <= 8) return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5)}`
  if (d.length <= 12) return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8)}`
  return `${d.slice(0, 2)}.${d.slice(2, 5)}.${d.slice(5, 8)}/${d.slice(8, 12)}-${d.slice(12)}`
}

export function normalizarCnpj(valor) {
  return String(valor || '')
    .toUpperCase()
    .replace(/[^0-9A-Z]/g, '')
}

export function emailValido(valor) {
  const e = String(valor || '').trim().toLowerCase()
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)
}

export function normalizeEmail(value) {
  return String(value ?? '').trim().toLowerCase()
}
