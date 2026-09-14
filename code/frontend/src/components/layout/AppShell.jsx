import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { ehSuperAdmin, temPermissao } from '../../utils/format'
import LogoGestorJob from '../brand/LogoGestorJob.jsx'
import NotificacoesBell from './NotificacoesBell.jsx'

function navClass({ isActive }) {
  return `rounded-lg px-3 py-2 text-sm font-bold ${
    isActive ? 'bg-white/12 text-white' : 'text-white/80 hover:bg-white/8'
  }`
}

function toggleClass({ isActive }) {
  return `rounded-md px-3 py-1.5 text-xs font-extrabold ${
    isActive ? 'bg-[var(--moss)] text-white' : 'text-[var(--moss)] hover:bg-[var(--moss-soft)]'
  }`
}

function NavSec({ label, children }) {
  return (
    <div className="mt-4 first:mt-0">
      <p className="px-3 pb-1 text-[10px] font-extrabold tracking-wider uppercase text-white/45">{label}</p>
      <div className="flex flex-col gap-1">{children}</div>
    </div>
  )
}

export default function AppShell({ title, cta, children }) {
  const { user, logout } = useAuth()
  const location = useLocation()
  const submittingRef = useRef(false)
  const menuRef = useRef(null)
  const [saindo, setSaindo] = useState(false)
  const [menuAberto, setMenuAberto] = useState(false)
  const superAdmin = ehSuperAdmin(user)
  const visaoTarefas = !superAdmin && (location.pathname === '/' || location.pathname === '/lista' || location.pathname.startsWith('/tarefas'))
  const verConfig = Boolean(user?.permissoes?.ver_config)
  const verFinanceiro = temPermissao(user, 'ver_financeiro')

  useEffect(() => {
    if (!menuAberto) return undefined
    function fechar(event) {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setMenuAberto(false)
      }
    }
    document.addEventListener('mousedown', fechar)
    return () => document.removeEventListener('mousedown', fechar)
  }, [menuAberto])

  async function onLogout() {
    if (submittingRef.current) return
    submittingRef.current = true
    setSaindo(true)
    setMenuAberto(false)
    try {
      await logout()
    } catch {
      submittingRef.current = false
      setSaindo(false)
    }
  }

  const iniciais = (user?.name || '?')
    .split(' ')
    .slice(0, 2)
    .map((p) => p[0])
    .join('')
    .toUpperCase()

  return (
    <div className="flex min-h-screen">
      <aside className="flex w-[220px] shrink-0 flex-col overflow-hidden bg-[var(--moss)] px-3 py-4 text-white">
        <div className="px-1">
          <LogoGestorJob variante="escuro" className="h-11 w-auto max-w-full" />
        </div>
        <nav aria-label="Principal" className="mt-6 flex min-h-0 flex-1 flex-col overflow-y-auto">
          {superAdmin ? (
            <NavLink to="/empresas" className={navClass}>
              Empresas
            </NavLink>
          ) : (
            <>
              <NavSec label="Operação">
                <NavLink to="/" end className={navClass}>
                  Kanban
                </NavLink>
                <NavLink to="/lista" className={navClass}>
                  Lista
                </NavLink>
              </NavSec>
              <NavSec label="Cadastros">
                <NavLink to="/clientes" className={navClass}>
                  Clientes / Fornecedores
                </NavLink>
                <NavLink to="/servicos" className={navClass}>
                  Serviços
                </NavLink>
                <NavLink to="/colaboradores" className={navClass}>
                  Colaboradores
                </NavLink>
              </NavSec>
              {verFinanceiro ? (
                <NavSec label="Inteligência">
                  <NavLink to="/dashboard" className={navClass}>
                    Dashboard
                  </NavLink>
                  <NavLink to="/relatorios/margem" className={navClass}>
                    Margem
                  </NavLink>
                  <NavLink to="/relatorios/atrasos" className={navClass}>
                    Atrasos
                  </NavLink>
                  <NavLink to="/relatorios/horas" className={navClass}>
                    Horas
                  </NavLink>
                  <NavLink to="/relatorios/carga" className={navClass}>
                    Carga
                  </NavLink>
                </NavSec>
              ) : null}
              {verConfig ? (
                <NavSec label="Config">
                  <NavLink to="/permissoes" className={navClass}>
                    Permissões
                  </NavLink>
                  <NavLink to="/config" className={navClass}>
                    Configurações
                  </NavLink>
                </NavSec>
              ) : null}
            </>
          )}
        </nav>
        <div className="mt-auto px-2 text-xs text-white/70">
          <p className="font-bold text-white">{user?.empresa?.nome || 'Plataforma'}</p>
          <p>{user?.name}</p>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center gap-3 border-b border-[var(--line)] bg-[var(--surface)] px-5 py-3">
          <h1 className="m-0 text-lg font-extrabold text-[var(--moss)]">{title}</h1>
          {visaoTarefas ? (
            <div className="flex rounded-lg border border-[var(--line)] p-0.5" data-testid="view-toggle">
              <NavLink to="/" end className={toggleClass}>
                Kanban
              </NavLink>
              <NavLink to="/lista" className={toggleClass}>
                Lista
              </NavLink>
            </div>
          ) : null}
          <span className="flex-1" />
          {cta?.to ? (
            <Link
              to={cta.to}
              className="rounded-lg bg-[var(--orange)] px-3 py-2 text-sm font-extrabold text-white hover:brightness-110"
            >
              {cta.label}
            </Link>
          ) : null}
          {cta?.onClick ? (
            <button
              type="button"
              onClick={cta.onClick}
              className="rounded-lg bg-[var(--orange)] px-3 py-2 text-sm font-extrabold text-white hover:brightness-110"
            >
              {cta.label}
            </button>
          ) : null}
          {!superAdmin ? <NotificacoesBell /> : null}
          <div ref={menuRef} className="relative">
            <button
              type="button"
              data-testid="btn-conta-menu"
              aria-expanded={menuAberto}
              aria-haspopup="menu"
              aria-label="Menu da conta"
              onClick={() => setMenuAberto((v) => !v)}
              className="grid h-9 w-9 place-items-center rounded-full bg-[var(--moss-soft)] text-xs font-extrabold text-[var(--moss)] hover:brightness-95"
            >
              {iniciais}
            </button>
            {menuAberto ? (
              <div
                data-testid="conta-menu-panel"
                role="menu"
                className="absolute right-0 z-40 mt-2 min-w-[160px] overflow-hidden rounded-[10px] border border-[var(--line)] bg-[var(--surface)] shadow-[0_12px_32px_rgba(26,34,32,0.14)]"
              >
                <Link
                  to="/perfil"
                  role="menuitem"
                  onClick={() => setMenuAberto(false)}
                  className="block px-3 py-2.5 text-sm font-bold text-[var(--moss)] hover:bg-[var(--moss-soft)]/40"
                >
                  Minha conta
                </Link>
                <button
                  type="button"
                  role="menuitem"
                  onClick={onLogout}
                  disabled={saindo}
                  className="block w-full border-t border-[var(--line)] px-3 py-2.5 text-left text-sm font-bold text-[var(--moss)] hover:bg-[var(--moss-soft)]/40 disabled:opacity-70"
                >
                  {saindo ? 'Saindo…' : 'Sair'}
                </button>
              </div>
            ) : null}
          </div>
        </header>
        <div className="min-h-0 flex-1 overflow-auto p-5">{children}</div>
      </div>
      {cta?.to && visaoTarefas && location.pathname !== '/tarefas/nova' ? (
        <Link
          to={cta.to}
          data-testid="fab-nova-tarefa"
          className="fixed right-5 bottom-5 z-30 rounded-full bg-[var(--orange)] px-4 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(232,93,4,0.35)] md:hidden"
        >
          {cta.label}
        </Link>
      ) : null}
    </div>
  )
}
