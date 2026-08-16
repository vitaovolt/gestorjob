import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { createServico, deleteServico, getServico, updateServico } from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import { useToast } from '../context/ToastContext'
import { maskMoneyBR, moneyFromNumber, parseMoneyBR } from '../utils/masks'

const DIAS = [
  ['seg', 'Seg'],
  ['ter', 'Ter'],
  ['qua', 'Qua'],
  ['qui', 'Qui'],
  ['sex', 'Sex'],
  ['sab', 'Sáb'],
  ['dom', 'Dom'],
]

const VAZIO = {
  nome: '',
  descricao: '',
  preco_venda: '',
  custo_estimado: '',
  tempo_estimado_minutos: '',
  checklist_texto: '',
  frequencia: '',
  dias: [],
  prazo_d_menos: '1',
}

function checklistParaTexto(lista) {
  return Array.isArray(lista) ? lista.join('\n') : ''
}

function textoParaChecklist(texto) {
  return String(texto || '')
    .split('\n')
    .map((linha) => linha.trim())
    .filter(Boolean)
}

export default function ServicoFormPage() {
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
    getServico(id)
      .then((payload) => {
        const s = payload.data
        const rec = s.recorrencia || {}
        setForm({
          nome: s.nome || '',
          descricao: s.descricao || '',
          preco_venda: moneyFromNumber(s.preco_venda),
          custo_estimado: moneyFromNumber(s.custo_estimado),
          tempo_estimado_minutos: s.tempo_estimado_minutos ?? '',
          checklist_texto: checklistParaTexto(s.checklist_padrao),
          frequencia: rec.frequencia || '',
          dias: Array.isArray(rec.dias) ? rec.dias : [],
          prazo_d_menos: rec.prazo_d_menos ?? '1',
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

  function toggleDia(dia) {
    setForm((atual) => ({
      ...atual,
      dias: atual.dias.includes(dia) ? atual.dias.filter((d) => d !== dia) : [...atual.dias, dia],
    }))
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
      custo_estimado: custo,
      tempo_estimado_minutos: form.tempo_estimado_minutos === '' ? null : Number(form.tempo_estimado_minutos),
      checklist_padrao: textoParaChecklist(form.checklist_texto),
      recorrencia: form.frequencia
        ? {
            frequencia: form.frequencia,
            dias: form.dias,
            prazo_d_menos: form.prazo_d_menos === '' ? 1 : Number(form.prazo_d_menos),
          }
        : null,
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
          <label className="block text-sm font-bold text-[var(--moss)]">
            Custo estimado (R$)
            <input
              inputMode="numeric"
              value={form.custo_estimado}
              onChange={(e) => setCampo('custo_estimado', maskMoneyBR(e.target.value))}
              className={campo}
              placeholder="0,00"
            />
          </label>
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
          <label className="block text-sm font-bold text-[var(--moss)]">
            Frequência
            <select
              value={form.frequencia}
              onChange={(e) => setCampo('frequencia', e.target.value)}
              className={campo}
              data-testid="servico-frequencia"
            >
              <option value="">Sem recorrência</option>
              <option value="semanal">Semanal</option>
              <option value="mensal">Mensal</option>
            </select>
          </label>
          {form.frequencia ? (
            <>
              <fieldset className="md:col-span-2">
                <legend className="text-sm font-bold text-[var(--moss)]">Dias</legend>
                <div className="mt-2 flex flex-wrap gap-2">
                  {DIAS.map(([valor, label]) => (
                    <label
                      key={valor}
                      className={`cursor-pointer rounded-full border px-3 py-1 text-sm font-bold ${
                        form.dias.includes(valor)
                          ? 'border-[var(--orange)] bg-[var(--orange-soft)] text-[var(--orange)]'
                          : 'border-[var(--line)] text-[var(--moss)]'
                      }`}
                    >
                      <input
                        type="checkbox"
                        className="sr-only"
                        checked={form.dias.includes(valor)}
                        onChange={() => toggleDia(valor)}
                      />
                      {label}
                    </label>
                  ))}
                </div>
              </fieldset>
              <label className="block text-sm font-bold text-[var(--moss)]">
                Prazo (D-menos)
                <input
                  type="number"
                  min="0"
                  value={form.prazo_d_menos}
                  onChange={(e) => setCampo('prazo_d_menos', e.target.value)}
                  className={campo}
                />
              </label>
            </>
          ) : null}
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Checklist padrão
            <textarea
              value={form.checklist_texto}
              onChange={(e) => setCampo('checklist_texto', e.target.value)}
              className={`${campo} min-h-[120px]`}
              placeholder={'Briefing\nArte\nCopy'}
              data-testid="servico-checklist"
            />
            <span className="mt-1 block text-xs font-medium text-[var(--muted)]">Um item por linha. Novas tarefas herdam esta lista.</span>
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
