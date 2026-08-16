import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { createCliente, deleteCliente, getCliente, updateCliente } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import CampoData from '../components/ui/CampoData.jsx'
import { useToast } from '../context/ToastContext'
import {
  dateBRToISO,
  emailValido,
  isoToDateBR,
  maskCnpj,
  maskMoneyBR,
  maskPhoneBR,
  moneyFromNumber,
  normalizeEmail,
  normalizarCnpj,
  onlyDigits,
  parseMoneyBR,
} from '../utils/masks'

const VAZIO = {
  nome_fantasia: '',
  razao_social: '',
  cnpj: '',
  segmento: '',
  status: 'ativo',
  contato_nome: '',
  email: '',
  whatsapp: '',
  inicio_parceria: '',
  pasta_drive_url: '',
  fee_mensal: '',
  dia_vencimento: '',
  tipo_faturamento: 'mensal',
  observacoes: '',
}

export default function ClienteFormPage() {
  const { id } = useParams()
  const novo = !id
  const navigate = useNavigate()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(VAZIO)
  const [submitting, setSubmitting] = useState(false)
  const [excluindo, setExcluindo] = useState(false)
  const [confirmarExcluir, setConfirmarExcluir] = useState(false)
  const [erro, setErro] = useState('')

  useEffect(() => {
    if (novo) return undefined
    getCliente(id)
      .then((payload) => {
        const c = payload.data
        setForm({
          nome_fantasia: c.nome_fantasia || '',
          razao_social: c.razao_social || '',
          cnpj: maskCnpj(c.cnpj || ''),
          segmento: c.segmento || '',
          status: c.status || 'ativo',
          contato_nome: c.contato_nome || '',
          email: c.email || '',
          whatsapp: maskPhoneBR(c.whatsapp || ''),
          inicio_parceria: isoToDateBR(c.inicio_parceria),
          pasta_drive_url: c.pasta_drive_url || '',
          fee_mensal: moneyFromNumber(c.fee_mensal),
          dia_vencimento: c.dia_vencimento ?? '',
          tipo_faturamento: c.tipo_faturamento || 'mensal',
          observacoes: c.observacoes || '',
        })
      })
      .catch(() => {
        showToast('Cliente não encontrado.', 'erro')
        navigate('/clientes', { replace: true })
      })
    return undefined
  }, [id, novo, navigate, showToast])

  function setCampo(campo, valor) {
    setForm((atual) => ({ ...atual, [campo]: valor }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.nome_fantasia.trim()) {
      setErro('Informe o nome fantasia.')
      return
    }
    const email = form.email.trim() ? normalizeEmail(form.email) : null
    if (email && !emailValido(email)) {
      setErro('Informe um e-mail válido.')
      return
    }
    const cnpj = normalizarCnpj(form.cnpj) || null
    if (form.cnpj.trim() && (!cnpj || cnpj.length !== 14)) {
      setErro('CNPJ incompleto. Use o padrão com 14 posições (letras ou números).')
      return
    }
    if (form.inicio_parceria.trim() && !dateBRToISO(form.inicio_parceria)) {
      setErro('Data da parceria inválida. Use DD/MM/AAAA.')
      return
    }
    const fee = form.fee_mensal === '' ? null : parseMoneyBR(form.fee_mensal)
    if (form.fee_mensal !== '' && !Number.isFinite(fee)) {
      setErro('Fee inválido.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    const payload = {
      nome_fantasia: form.nome_fantasia.trim(),
      razao_social: form.razao_social.trim() || null,
      cnpj,
      segmento: form.segmento.trim() || null,
      status: form.status,
      contato_nome: form.contato_nome.trim() || null,
      email,
      whatsapp: onlyDigits(form.whatsapp) || null,
      inicio_parceria: form.inicio_parceria.trim() ? dateBRToISO(form.inicio_parceria) : null,
      pasta_drive_url: form.pasta_drive_url.trim() || null,
      fee_mensal: fee,
      dia_vencimento: form.dia_vencimento === '' ? null : Number(form.dia_vencimento),
      tipo_faturamento: form.tipo_faturamento,
      observacoes: form.observacoes.trim() || null,
    }

    try {
      if (novo) {
        await createCliente(payload)
        showToast('Cliente criado')
      } else {
        await updateCliente(id, payload)
        showToast('Cliente atualizado')
      }
      navigate('/clientes', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.cnpj?.[0] ||
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.nome_fantasia?.[0] ||
        err.response?.data?.message ||
        'Não foi possível salvar o cliente.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  async function onExcluir() {
    if (submittingRef.current) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteCliente(id)
      showToast('Cliente removido')
      navigate('/clientes', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setExcluindo(false)
      setConfirmarExcluir(false)
      const msg = err.response?.data?.message || 'Não foi possível excluir o cliente.'
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <AppShell title={novo ? 'Novo cliente' : 'Editar cliente'}>
      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5">
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)]">
            Nome fantasia
            <input
              value={form.nome_fantasia}
              onChange={(e) => setCampo('nome_fantasia', e.target.value)}
              className={campo}
              required
              data-testid="cliente-nome"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Razão social
            <input value={form.razao_social} onChange={(e) => setCampo('razao_social', e.target.value)} className={campo} />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            CNPJ
            <input
              value={form.cnpj}
              onChange={(e) => setCampo('cnpj', maskCnpj(e.target.value))}
              className={campo}
              placeholder="12.ABC.345/01DE-35"
              autoCapitalize="characters"
              spellCheck={false}
              data-testid="cliente-cnpj"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Segmento
            <input value={form.segmento} onChange={(e) => setCampo('segmento', e.target.value)} className={campo} />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Status
            <select value={form.status} onChange={(e) => setCampo('status', e.target.value)} className={campo}>
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
              <option value="prospect">Prospect</option>
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Contato
            <input value={form.contato_nome} onChange={(e) => setCampo('contato_nome', e.target.value)} className={campo} />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            E-mail
            <input
              type="email"
              inputMode="email"
              autoCapitalize="none"
              autoCorrect="off"
              spellCheck={false}
              value={form.email}
              onChange={(e) => setCampo('email', e.target.value)}
              className={campo}
              placeholder="contato@cliente.com"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            WhatsApp
            <input
              value={form.whatsapp}
              onChange={(e) => setCampo('whatsapp', maskPhoneBR(e.target.value))}
              className={campo}
              inputMode="tel"
              placeholder="(11) 99999-9999"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Início da parceria
            <CampoData
              value={form.inicio_parceria}
              onChange={(valor) => setCampo('inicio_parceria', valor)}
              className={campo}
              testId="cliente-inicio"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Fee mensal (R$)
            <input
              inputMode="numeric"
              value={form.fee_mensal}
              onChange={(e) => setCampo('fee_mensal', maskMoneyBR(e.target.value))}
              className={campo}
              placeholder="0,00"
              data-testid="cliente-fee"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Dia vencimento
            <input
              type="number"
              min="1"
              max="28"
              value={form.dia_vencimento}
              onChange={(e) => setCampo('dia_vencimento', e.target.value)}
              className={campo}
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Tipo de faturamento
            <select
              value={form.tipo_faturamento}
              onChange={(e) => setCampo('tipo_faturamento', e.target.value)}
              className={campo}
            >
              <option value="mensal">Mensal</option>
              <option value="projeto">Projeto</option>
              <option value="hora">Hora</option>
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Pasta Drive
            <input
              value={form.pasta_drive_url}
              onChange={(e) => setCampo('pasta_drive_url', e.target.value)}
              className={campo}
              placeholder="https://drive.google.com/…"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Observações
            <textarea
              value={form.observacoes}
              onChange={(e) => setCampo('observacoes', e.target.value)}
              className={`${campo} min-h-[80px]`}
            />
          </label>
        </div>

        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}

        <div className="mt-5 flex flex-wrap items-center gap-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
          >
            {submitting ? 'Processando…' : novo ? 'Salvar cliente' : 'Salvar alterações'}
          </button>
          <Link to="/clientes" className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]">
            Cancelar
          </Link>
          {!novo ? (
            <button
              type="button"
              onClick={() => setConfirmarExcluir(true)}
              disabled={submitting || excluindo}
              className="ml-auto rounded-lg border border-[#f3c4c0] px-4 py-2.5 text-sm font-bold text-[#9b1c1c]"
            >
              Excluir
            </button>
          ) : null}
        </div>
      </form>

      {confirmarExcluir ? (
        <div className="fixed inset-0 z-[60] grid place-items-center bg-black/30 p-4">
          <div className="w-full max-w-sm rounded-[12px] border border-[var(--line)] bg-white p-5">
            <p className="m-0 font-extrabold text-[var(--moss)]">Excluir este cliente?</p>
            <p className="mt-2 mb-0 text-sm text-[var(--muted)]">
              Só funciona se não houver tarefas ligadas a ele.
            </p>
            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                disabled={excluindo}
                onClick={() => setConfirmarExcluir(false)}
                className="rounded-lg border border-[var(--line)] px-3 py-2 text-sm font-bold"
              >
                Cancelar
              </button>
              <button
                type="button"
                data-testid="cliente-confirmar-excluir"
                disabled={excluindo}
                onClick={onExcluir}
                className="rounded-lg bg-[#b42318] px-3 py-2 text-sm font-extrabold text-white disabled:opacity-70"
              >
                {excluindo ? 'Processando…' : 'Excluir cliente'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </AppShell>
  )
}
