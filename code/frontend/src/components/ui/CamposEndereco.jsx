import { useRef, useState } from 'react'
import { consultarCep } from '../../api/dominio'
import { maskCep, maskUf, onlyDigits } from '../../utils/masks'

export default function CamposEndereco({ form, setCampo, setCampos, campoClass }) {
  const [buscando, setBuscando] = useState(false)
  const [aviso, setAviso] = useState('')
  const ultimoRef = useRef('')

  async function buscar(digits) {
    if (digits.length !== 8 || digits === ultimoRef.current) return
    ultimoRef.current = digits
    setBuscando(true)
    setAviso('')
    try {
      const payload = await consultarCep(digits)
      const d = payload.data || {}
      setCampos({
        cep: maskCep(d.cep || digits),
        logradouro: d.logradouro || '',
        bairro: d.bairro || '',
        cidade: d.cidade || '',
        uf: d.uf || '',
      })
    } catch (err) {
      ultimoRef.current = ''
      setAviso(err.response?.data?.message || 'Não achamos o CEP. Preencha o endereço na mão.')
    } finally {
      setBuscando(false)
    }
  }

  function onCepChange(valor) {
    const masked = maskCep(valor)
    setCampo('cep', masked)
    const digits = onlyDigits(masked)
    if (digits.length === 8) {
      buscar(digits)
    }
  }

  return (
    <fieldset className="md:col-span-2 grid gap-4 md:grid-cols-6">
      <legend className="mb-1 text-sm font-extrabold text-[var(--moss)]">Endereço</legend>
      <p className="md:col-span-6 m-0 text-xs text-[var(--muted)]">
        Ao informar o CEP, o endereço é preenchido automaticamente.
      </p>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
        CEP
        <input
          value={form.cep}
          onChange={(e) => onCepChange(e.target.value)}
          className={campoClass}
          inputMode="numeric"
          placeholder="00000-000"
          data-testid="cliente-cep"
        />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-4">
        Logradouro
        <input
          value={form.logradouro}
          onChange={(e) => setCampo('logradouro', e.target.value)}
          className={campoClass}
          placeholder={buscando ? 'Buscando…' : 'Rua, avenida…'}
          data-testid="cliente-logradouro"
        />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
        Número
        <input
          value={form.numero}
          onChange={(e) => setCampo('numero', e.target.value)}
          className={campoClass}
          data-testid="cliente-numero"
        />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-4">
        Complemento
        <input
          value={form.complemento}
          onChange={(e) => setCampo('complemento', e.target.value)}
          className={campoClass}
        />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-2">
        Bairro
        <input value={form.bairro} onChange={(e) => setCampo('bairro', e.target.value)} className={campoClass} />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-3">
        Cidade
        <input
          value={form.cidade}
          onChange={(e) => setCampo('cidade', e.target.value)}
          className={campoClass}
          data-testid="cliente-cidade"
        />
      </label>
      <label className="block text-sm font-bold text-[var(--moss)] md:col-span-1">
        UF
        <input
          value={form.uf}
          onChange={(e) => setCampo('uf', maskUf(e.target.value))}
          className={campoClass}
          maxLength={2}
          data-testid="cliente-uf"
        />
      </label>
      {aviso ? <p className="md:col-span-6 m-0 text-sm font-semibold text-[#b42318]">{aviso}</p> : null}
    </fieldset>
  )
}
