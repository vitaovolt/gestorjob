export function formatarData(iso) {
  if (!iso) return '—'
  const data = new Date(iso)
  if (Number.isNaN(data.getTime())) return '—'
  return data.toLocaleDateString('pt-BR')
}

export function formatarBRL(valor) {
  const n = Number(valor)
  if (Number.isNaN(n)) return '—'
  return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function podeCadastrarClientes(user) {
  return user?.permissoes?.gerir_cadastros === true
    || user?.papel === 'admin'
    || user?.papel === 'gerente'
}

export function podeGerirCadastros(user) {
  return podeCadastrarClientes(user)
}

export function temPermissao(user, chave) {
  return Boolean(user?.permissoes?.[chave])
}

export function ehSuperAdmin(user) {
  return user?.papel === 'super_admin'
}

export function destinoInicial(user, from) {
  if (ehSuperAdmin(user)) {
    if (typeof from === 'string' && (from.startsWith('/empresas') || from === '/perfil')) {
      return from
    }
    return '/empresas'
  }
  if (user?.wizard_pendente) {
    return '/wizard'
  }
  if (typeof from === 'string' && from !== '/login' && from !== '/wizard') {
    return from
  }
  return '/'
}

export function formatarBytes(bytes) {
  const n = Number(bytes)
  if (!Number.isFinite(n) || n <= 0) return '—'
  if (n < 1024) return `${n} B`
  const kb = n / 1024
  if (kb < 1024) return `${kb < 10 ? kb.toFixed(1) : Math.round(kb)} KB`
  const mb = kb / 1024
  return `${mb < 10 ? mb.toFixed(1) : Math.round(mb)} MB`
}

export function formatarMinutos(minutos) {
  const n = Number(minutos)
  if (!Number.isFinite(n) || n <= 0) return '—'
  if (n < 60) return `${n} min`
  const h = Math.floor(n / 60)
  const m = n % 60
  return m ? `${h} h ${m} min` : `${h} h`
}

export function papelLabel(papel) {
  return (
    {
      admin: 'Admin',
      gerente: 'Gerente',
      colaborador: 'Colaborador',
      visualizador: 'Visualizador',
      super_admin: 'Super Admin',
    }[papel] || papel
  )
}
