import { useRef, useState } from 'react'
import { createTarefa } from '../../api/dominio'
import { useToast } from '../../context/ToastContext'

export default function CreateTaskModal({ clientes, servicos, onClose, onCriada }) {
  const { showToast } = useToast()
  const submittingRef = useRef(false)
  const [clienteId, setClienteId] = useState(clientes[0]?.id || '')
  const [servicoId, setServicoId] = useState(servicos[0]?.id || '')
  const [titulo, setTitulo] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  async function onSubmit(event) {
    event.preventDefault()
    if (submittingRef.current) return
    if (!titulo.trim() || !clienteId) {
      setError('Informe cliente e título.')
      return
    }

    submittingRef.current = true
    setSubmitting(true)
    setError('')
    try {
      const payload = await createTarefa({
        cliente_id: Number(clienteId),
        servico_id: servicoId ? Number(servicoId) : undefined,
        titulo: titulo.trim(),
      })
      showToast('Tarefa criada')
      onCriada(payload.data)
    } catch (err) {
      submittingRef.current = false
      setSubmitting(false)
      setError(err.response?.data?.message || 'Não foi possível criar a tarefa.')
    }
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/30 p-4">
      <form
        onSubmit={onSubmit}
        className="w-full max-w-md rounded-[12px] border border-[var(--line)] bg-white p-5 shadow-xl"
      >
        <div className="flex items-center gap-2">
          <h2 className="m-0 text-lg font-extrabold text-[var(--moss)]">Nova tarefa</h2>
          <span className="flex-1" />
          <button type="button" onClick={onClose} className="text-sm font-bold text-[var(--muted)]">
            Cancelar
          </button>
        </div>

        <label className="mt-4 block text-sm font-bold text-[var(--moss)]">
          Cliente
          <select
            value={clienteId}
            onChange={(e) => setClienteId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2"
          >
            {clientes.map((c) => (
              <option key={c.id} value={c.id}>
                {c.nome_fantasia}
              </option>
            ))}
          </select>
        </label>

        <label className="mt-3 block text-sm font-bold text-[var(--moss)]">
          Serviço
          <select
            value={servicoId}
            onChange={(e) => setServicoId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2"
          >
            <option value="">Sem serviço</option>
            {servicos.map((s) => (
              <option key={s.id} value={s.id}>
                {s.nome}
              </option>
            ))}
          </select>
        </label>

        <label className="mt-3 block text-sm font-bold text-[var(--moss)]">
          Título
          <input
            value={titulo}
            onChange={(e) => setTitulo(e.target.value)}
            className="mt-1 w-full rounded-lg border border-[var(--line)] px-3 py-2"
            placeholder="Reels — Cliente"
            required
          />
        </label>

        {error ? <p className="mt-3 text-sm font-semibold text-[#b42318]">{error}</p> : null}

        <button
          type="submit"
          disabled={submitting}
          className="mt-4 w-full rounded-lg bg-[var(--orange)] py-2.5 text-sm font-extrabold text-white disabled:opacity-70"
        >
          {submitting ? 'Processando…' : 'Criar tarefa'}
        </button>
      </form>
    </div>
  )
}
