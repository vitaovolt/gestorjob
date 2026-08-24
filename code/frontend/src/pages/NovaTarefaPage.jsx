import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import {
  createTarefa,
  listClientes,
  listColaboradores,
  listServicos,
  mensagemErroUploadAnexo,
  uploadAnexo,
  validarAnexoCliente,
} from '../api/dominio'
import AppShell from '../components/layout/AppShell.jsx'
import CampoArquivo from '../components/ui/CampoArquivo.jsx'
import CampoData from '../components/ui/CampoData.jsx'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { temPermissao } from '../utils/format'
import { dateBRToISO, isoToDateBR } from '../utils/masks'

const DIAS = [
  ['seg', 'Seg'],
  ['ter', 'Ter'],
  ['qua', 'Qua'],
  ['qui', 'Qui'],
  ['sex', 'Sex'],
  ['sab', 'Sáb'],
  ['dom', 'Dom'],
]

function hojeBR() {
  const d = new Date()
  return isoToDateBR(
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`,
  )
}

const campo =
  'mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2 font-medium text-[var(--ink)] outline-none focus:border-[var(--moss)]'

export default function NovaTarefaPage() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const podeAlocar = temPermissao(user, 'alocar_responsaveis')
  const [clientes, setClientes] = useState([])
  const [servicos, setServicos] = useState([])
  const [colaboradores, setColaboradores] = useState([])
  const [submitting, setSubmitting] = useState(false)
  const [carregando, setCarregando] = useState(true)
  const [erro, setErro] = useState('')
  const [arquivos, setArquivos] = useState([])
  const [form, setForm] = useState({
    cliente_id: '',
    servico_id: '',
    titulo: '',
    responsavel_ids: [],
    briefing: '',
    checklist_texto: '',
    inicio: hojeBR(),
    entrega: hojeBR(),
    frequencia: 'nunca',
    dias: [],
  })

  useEffect(() => {
    Promise.all([listClientes(), listServicos(), listColaboradores()])
      .then(([c, s, col]) => {
        setClientes(c.data || [])
        setServicos(s.data || [])
        setColaboradores(col.data || [])
        setForm((atual) => ({
          ...atual,
          cliente_id: atual.cliente_id || String((c.data || [])[0]?.id || ''),
        }))
      })
      .catch(() => {
        showToast('Não foi possível carregar os cadastros.', 'erro')
      })
      .finally(() => setCarregando(false))
  }, [showToast])

  function setCampo(chave, valor) {
    setForm((atual) => ({ ...atual, [chave]: valor }))
  }

  function toggleDia(dia) {
    setForm((atual) => ({
      ...atual,
      dias: atual.dias.includes(dia) ? atual.dias.filter((d) => d !== dia) : [...atual.dias, dia],
    }))
  }

  function toggleResp(id) {
    const n = Number(id)
    setForm((atual) => ({
      ...atual,
      responsavel_ids: atual.responsavel_ids.includes(n)
        ? atual.responsavel_ids.filter((x) => x !== n)
        : [...atual.responsavel_ids, n],
    }))
  }

  function onArquivos(lista) {
    const ok = []
    for (const arquivo of lista) {
      const msg = validarAnexoCliente(arquivo)
      if (msg) {
        showToast(msg, 'erro')
        continue
      }
      ok.push(arquivo)
    }
    if (ok.length) setArquivos((atual) => [...atual, ...ok])
  }

  function botoesSalvar() {
    return (
      <div className="flex flex-wrap items-center gap-2">
        <button
          type="submit"
          disabled={submitting || carregando || !form.cliente_id}
          data-testid="tarefa-salvar"
          className="rounded-lg bg-[var(--orange)] px-4 py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Criar tarefa'}
        </button>
        <Link to="/" className="rounded-lg border border-[var(--line)] px-4 py-2.5 text-sm font-bold text-[var(--moss)]">
          Cancelar
        </Link>
      </div>
    )
  }

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!form.titulo.trim() || !form.cliente_id) {
      setErro('Informe cliente e título.')
      return
    }
    if (form.frequencia === 'semanal' && form.dias.length === 0) {
      setErro('Marque os dias da repetição semanal.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setErro('')

    const checklist = form.checklist_texto
      .split('\n')
      .map((l) => l.trim())
      .filter(Boolean)

    try {
      const payload = await createTarefa({
        cliente_id: Number(form.cliente_id),
        servico_id: form.servico_id ? Number(form.servico_id) : undefined,
        titulo: form.titulo.trim(),
        briefing: form.briefing.trim() || null,
        inicio_em: dateBRToISO(form.inicio) || undefined,
        prazo_em: dateBRToISO(form.entrega) || undefined,
        responsavel_ids: podeAlocar ? form.responsavel_ids : [],
        checklist,
        repeticao: {
          frequencia: form.frequencia,
          dias: form.frequencia === 'semanal' ? form.dias : [],
        },
      })
      const tarefa = payload.data
      for (const arquivo of arquivos) {
        await uploadAnexo(tarefa.id, arquivo)
      }
      showToast(form.frequencia === 'nunca' ? 'Tarefa criada' : 'Tarefa criada e cards gerados')
      navigate('/', { replace: true })
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      const msg =
        err.response?.data?.errors?.['repeticao.dias']?.[0] ||
        err.response?.data?.errors?.titulo?.[0] ||
        mensagemErroUploadAnexo(err) ||
        err.response?.data?.message ||
        'Não foi possível criar a tarefa.'
      setErro(msg)
      showToast(msg, 'erro')
    }
  }

  return (
    <AppShell title="Nova tarefa">
      <form onSubmit={onSubmit} className="max-w-3xl rounded-[12px] border border-[var(--line)] bg-white p-5" data-testid="form-nova-tarefa">
        <div className="mb-4 flex items-start justify-between gap-3">
          <p className="m-0 text-sm text-[var(--muted)]">
            Lançamento rápido: briefing, prazo no fim do expediente e repetição na própria tarefa.
          </p>
          {botoesSalvar()}
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Título
            <input
              value={form.titulo}
              onChange={(e) => setCampo('titulo', e.target.value)}
              className={campo}
              placeholder="Logotipo EduCraft"
              required
              data-testid="tarefa-titulo"
            />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Cliente
            <select
              value={form.cliente_id}
              onChange={(e) => setCampo('cliente_id', e.target.value)}
              className={campo}
              data-testid="tarefa-cliente"
            >
              {clientes.map((c) => (
                <option key={c.id} value={c.id}>{c.nome_fantasia}</option>
              ))}
            </select>
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Serviço
            <select
              value={form.servico_id}
              onChange={(e) => setCampo('servico_id', e.target.value)}
              className={campo}
              data-testid="tarefa-servico"
            >
              <option value="">Sem serviço</option>
              {servicos.map((s) => (
                <option key={s.id} value={s.id}>{s.nome}</option>
              ))}
            </select>
          </label>

          {podeAlocar ? (
            <fieldset className="md:col-span-2">
              <legend className="text-sm font-bold text-[var(--moss)]">Responsáveis</legend>
              <div className="mt-2 flex flex-wrap gap-2" data-testid="tarefa-responsaveis">
                {colaboradores.map((p) => (
                  <label
                    key={p.id}
                    className={`cursor-pointer rounded-full border px-3 py-1 text-sm font-bold ${
                      form.responsavel_ids.includes(p.id)
                        ? 'border-[var(--orange)] bg-[var(--orange-soft)] text-[var(--orange)]'
                        : 'border-[var(--line)] text-[var(--moss)]'
                    }`}
                  >
                    <input
                      type="checkbox"
                      className="sr-only"
                      checked={form.responsavel_ids.includes(p.id)}
                      onChange={() => toggleResp(p.id)}
                    />
                    {p.name}
                  </label>
                ))}
              </div>
            </fieldset>
          ) : null}

          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Briefing
            <textarea
              value={form.briefing}
              onChange={(e) => setCampo('briefing', e.target.value)}
              className={`${campo} min-h-[88px]`}
              data-testid="tarefa-briefing"
            />
          </label>

          <div className="md:col-span-2">
            <p className="m-0 text-sm font-bold text-[var(--moss)]">Anexos</p>
            <CampoArquivo
              files={arquivos}
              onEscolher={onArquivos}
              onRemover={(indice) => setArquivos((atual) => atual.filter((_, i) => i !== indice))}
              disabled={submitting}
              testId="tarefa-anexos"
            />
          </div>

          <label className="block text-sm font-bold text-[var(--moss)]">
            Início
            <CampoData value={form.inicio} onChange={(v) => setCampo('inicio', v)} className={campo} testId="tarefa-inicio" />
          </label>
          <label className="block text-sm font-bold text-[var(--moss)]">
            Entrega
            <CampoData value={form.entrega} onChange={(v) => setCampo('entrega', v)} className={campo} testId="tarefa-entrega" />
            <span className="mt-1 block text-xs font-medium text-[var(--muted)]">O prazo fecha no fim do expediente.</span>
          </label>

          <label className="block text-sm font-bold text-[var(--moss)]">
            Repetição
            <select
              value={form.frequencia}
              onChange={(e) => setCampo('frequencia', e.target.value)}
              className={campo}
              data-testid="tarefa-repeticao"
            >
              <option value="nunca">Nunca</option>
              <option value="diaria">Diária (dias de expediente)</option>
              <option value="semanal">Semanal</option>
            </select>
          </label>

          {form.frequencia === 'semanal' ? (
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
          ) : null}

          <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
            Partes da tarefa (checklist)
            <textarea
              value={form.checklist_texto}
              onChange={(e) => setCampo('checklist_texto', e.target.value)}
              className={`${campo} min-h-[88px]`}
              placeholder={'Texto\nArte'}
              data-testid="tarefa-checklist"
            />
            <span className="mt-1 block text-xs font-medium text-[var(--muted)]">Um item por linha. Quem conclui avisa os demais.</span>
          </label>
        </div>

        {erro ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{erro}</p> : null}

        <div className="sticky bottom-0 mt-6 border-t border-[var(--line)] bg-white pt-4">{botoesSalvar()}</div>
      </form>
    </AppShell>
  )
}
