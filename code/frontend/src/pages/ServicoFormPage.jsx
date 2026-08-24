import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { createServico, deleteServico, getServico, updateServico } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { temPermissao } from '../utils/format'
import { maskMoneyBR, moneyFromNumber, parseMoneyBR } from '../utils/masks'

const VAZIO = {
  nome: '',
  descricao: '',
  preco_venda: '',
  custo_estimado: '',
  tempo_estimado_minutos: '',
}

export default function ServicoFormPage() {
  const { id } = useParams()
  const novo = !id
  const navigate = useNavigate()
  const { user } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [form, setForm] = useState(VAZIO)
  const [submitting, setSubmitting] = useState(false)
  const [excluindo, setExcluindo] = useState(false)
  const [confirmarExcluir, setConfirmarExcluir] = useState(false)
  const [erro, setErro] = useState('')
  const verCusto = temPermissao(user, 'ver_financeiro')

  useEffect(() => {
    if (novo) return undefined
    getServico(id)
      .then((payload) => {
        const s = payload.data
        setForm({
          nome: s.nome || '',
          descricao: s.descricao || '',
          preco_venda: moneyFromNumber(s.preco_venda),
          custo_estimado: moneyFromNumber(s.custo_estimado),
          tempo_estimado_minutos: s.tempo_estimado_minutos ?? '',
        })
      })
      .catch(() => {
        showToast('Serviço não encontrado.', 'erro')
        navigate('/servicos', { replace: true })
      })

    return undefined
  }, [id, novo, navigate, showToast])

  function setCampo(campo, valor) {
    setForm((atual) => ({ ...atual, [campo]: valor }))
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.nome.trim()) {
      setErro('Informe o nome comercial.')
      return
    }
    const preco = form.preco_venda === '' ? null : parseMoneyBR(form.preco_venda)
    if (form.preco_venda !== '' && !Number.isFinite(preco)) {
      setErro('Preço inválido.')
      return
    }
    const custo = form.custo_estimado === '' ? null : parseMoneyBR(form.custo_estimado)
    if (form.custo_estimado !== '' && !Number.isFinite(custo)) {
      setErro('Custo estimado inválido.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    const payload = {
      nome: form.nome.trim(),
      descricao: form.descricao.trim() || null,
      preco_venda: preco,
      tempo_estimado_minutos: form.tempo_estimado_minutos === '' ? null : Number(form.tempo_estimado_minutos),
    }
    if (verCusto) {
      payload.custo_estimado = custo
    }

    try {
      if (novo) {
        await createServico(payload)
        showToast('Serviço criado')
      } else {
        await updateServico(id, payload)
        showToast('Serviço atualizado')
      }
      navigate('/servicos', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.nome?.[0] ||
        err.response?.data?.message ||
        'Não foi possível salvar o serviço.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  async function onExcluir() {
    if (submittingRef.current) return
    submittingRef.current = true
    setExcluindo(true)
    try {
      await deleteServico(id)
      showToast('Serviço removido')
      navigate('/servicos', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setExcluindo(false)
      setConfirmarExcluir(false)
      const msg = err.response?.data?.message || 'Não foi possível excluir o serviço.'
      showToast(msg, 'erro')
    }
  }

  const campo =
    'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

  return (
    <AppShell title={novo ? 'Novo serviço' : 'Editar serviço'}>
      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5">
        <p className="mt-0 mb-4 text-sm text-[var(--muted)]">
          Catálogo comercial. Recorrência, responsáveis e prazo ficam na criação da tarefa.
        </p>
        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Nome comercial
            <input
              value={form.nome}
              onChange={(e) => setCampo('nome', e.target.value)}
              className={campo}
              required
              data-testid="servico-nome"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Preço de venda (R$)
            <input
              inputMode="numeric"
              value={form.preco_venda}
              onChange={(e) => setCampo('preco_venda', maskMoneyBR(e.target.value))}
              className={campo}
              placeholder="0,00"
              data-testid="servico-preco"
            />
          </label>
          {verCusto ? (
            <label className="block text-sm font-bold text-[var(--moss)]">
              Custo estimado (R$)
              <input
                inputMode="numeric"
                value={form.custo_estimado}
                onChange={(e) => setCampo('custo_estimado', maskMoneyBR(e.target.value))}
                className={campo}
                placeholder="0,00"
                data-testid="servico-custo"
              />
            </label>
          ) : null}
          <label className="block text-sm font-bold text-[var(--moss)]">
            Tempo estimado (minutos)
            <input
              type="number"
              min="0"
              value={form.tempo_estimado_minutos}
              onChange={(e) => setCampo('tempo_estimado_minutos', e.target.value)}
              className={campo}
              data-testid="servico-tempo"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Descrição
            <textarea
              value={form.descricao}
              onChange={(e) => setCampo('descricao', e.target.value)}
              className={`${campo} min-h-[72px]`}
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
            {submitting ? 'Processando…' : novo ? 'Salvar serviço' : 'Salvar alterações'}
          </button>
          <Link to="/servicos" className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]">
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
            <p className="m-0 font-extrabold text-[var(--moss)]">Excluir este serviço?</p>
            <p className="mt-2 mb-0 text-sm text-[var(--muted)]">Só funciona se não houver tarefas ligadas a ele.</p>
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
                data-testid="servico-confirmar-excluir"
                disabled={excluindo}
                onClick={onExcluir}
                className="rounded-lg bg-[#b42318] px-3 py-2 text-sm font-extrabold text-white disabled:opacity-70"
              >
                {excluindo ? 'Processando…' : 'Excluir serviço'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </AppShell>
  )
}
