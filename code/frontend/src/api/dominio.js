import client from './client'

export const listNotificacoes = () => client.get('/notificacoes').then((r) => r.data)
export const totalNaoLidas = () => client.get('/notificacoes/nao-lidas').then((r) => r.data)
export const marcarNotificacaoLida = (id) =>
  client.post(`/notificacoes/${id}/lida`).then((r) => r.data)
export const marcarTodasNotificacoesLidas = () =>
  client.post('/notificacoes/ler-todas').then((r) => r.data)

export const listClientes = () => client.get('/clientes').then((r) => r.data)
export const getCliente = (id) => client.get(`/clientes/${id}`).then((r) => r.data)
export const createCliente = (payload) => client.post('/clientes', payload).then((r) => r.data)
export const updateCliente = (id, payload) => client.put(`/clientes/${id}`, payload).then((r) => r.data)
export const deleteCliente = (id) => client.delete(`/clientes/${id}`).then((r) => r.data)

export const listServicos = () => client.get('/servicos').then((r) => r.data)
export const getServico = (id) => client.get(`/servicos/${id}`).then((r) => r.data)
export const createServico = (payload) => client.post('/servicos', payload).then((r) => r.data)
export const updateServico = (id, payload) => client.put(`/servicos/${id}`, payload).then((r) => r.data)
export const deleteServico = (id) => client.delete(`/servicos/${id}`).then((r) => r.data)

export const listColaboradores = () => client.get('/colaboradores').then((r) => r.data)
export const getColaborador = (id) => client.get(`/colaboradores/${id}`).then((r) => r.data)
export const createColaborador = (payload) => client.post('/colaboradores', payload).then((r) => r.data)
export const updateColaborador = (id, payload) => client.put(`/colaboradores/${id}`, payload).then((r) => r.data)
export const deleteColaborador = (id) => client.delete(`/colaboradores/${id}`).then((r) => r.data)

export const listTarefas = (params) => client.get('/tarefas', { params }).then((r) => r.data)
export const createTarefa = (payload) => client.post('/tarefas', payload).then((r) => r.data)
export const getTarefa = (id) => client.get(`/tarefas/${id}`).then((r) => r.data)
export const updateTarefa = (id, payload) => client.put(`/tarefas/${id}`, payload).then((r) => r.data)
export const iniciarTimer = (id, fase) => client.post(`/tarefas/${id}/timer`, { fase }).then((r) => r.data)
export const pausarTimer = (id) => client.post(`/tarefas/${id}/timer/pausar`).then((r) => r.data)
export const atualizarChecklist = (tarefaId, itemId, feito) =>
  client.put(`/tarefas/${tarefaId}/checklist/${itemId}`, { feito }).then((r) => r.data)
export const deleteTarefa = (id) => client.delete(`/tarefas/${id}`).then((r) => r.data)
/** Alinhado a TarefaAnexo::MIMES / MAX_KB no backend — allowlist fechada. */
export const ANEXO_MAX_BYTES = 10 * 1024 * 1024
export const ANEXO_EXTENSOES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx']
/** Só extensões da lista (sem image/* — senão o seletor libera BMP/TIFF/HEIC etc.). */
export const ANEXO_ACCEPT = ANEXO_EXTENSOES.map((e) => `.${e}`).join(',')
export const ANEXO_MSG_TIPO =
  'Arquivo fora da lista permitida (PDF, JPG, PNG, WEBP, GIF, Word ou Excel).'

/** Validação síncrona antes do POST — rejeita qualquer extensão fora da allowlist. */
export function validarAnexoCliente(arquivo) {
  const nome = arquivo?.name || ''
  const ponto = nome.lastIndexOf('.')
  const ext = ponto >= 0 ? nome.slice(ponto + 1).toLowerCase() : ''
  if (!ext || !ANEXO_EXTENSOES.includes(ext)) {
    return ANEXO_MSG_TIPO
  }
  if (arquivo.size > ANEXO_MAX_BYTES) {
    return 'O arquivo pode ter no máximo 10 MB.'
  }
  return null
}

export function mensagemErroUploadAnexo(err) {
  if (err?.code === 'ECONNABORTED' || /timeout/i.test(err?.message || '')) {
    return 'O envio demorou demais. Tente um arquivo menor.'
  }
  if (err?.response?.status === 413) {
    return 'Arquivo grande demais para o servidor.'
  }
  if (!err?.response) {
    return 'Falha de rede ao enviar o arquivo.'
  }
  return (
    err.response?.data?.errors?.arquivo?.[0] ||
    err.response?.data?.message ||
    'Não foi possível enviar o arquivo.'
  )
}

export const uploadAnexo = (tarefaId, arquivo) => {
  const form = new FormData()
  form.append('arquivo', arquivo)
  return client.post(`/tarefas/${tarefaId}/anexos`, form, { timeout: 60_000 }).then((r) => r.data)
}
export const deleteAnexo = (tarefaId, anexoId) =>
  client.delete(`/tarefas/${tarefaId}/anexos/${anexoId}`).then((r) => r.data)
export async function downloadAnexo(tarefaId, anexoId, nome) {
  const resposta = await client.get(`/tarefas/${tarefaId}/anexos/${anexoId}/download`, {
    responseType: 'blob',
  })
  const url = URL.createObjectURL(resposta.data)
  const link = document.createElement('a')
  link.href = url
  link.download = nome || 'anexo'
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.setTimeout(() => URL.revokeObjectURL(url), 1000)
}

export const getConfiguracao = () => client.get('/configuracao').then((r) => r.data)
export const updateConfiguracao = (payload) => client.put('/configuracao', payload).then((r) => r.data)
export const getPermissoes = () => client.get('/permissoes').then((r) => r.data)

export const getEmpresa = () => client.get('/empresa').then((r) => r.data)
export const listEmpresas = () => client.get('/empresas').then((r) => r.data)
export const getEmpresaPlataforma = (id) => client.get(`/empresas/${id}`).then((r) => r.data)
export const createEmpresa = (payload) => client.post('/empresas', payload).then((r) => r.data)
export const updateEmpresa = (id, payload) => client.put(`/empresas/${id}`, payload).then((r) => r.data)
export const reenviarConvite = (id) => client.post(`/empresas/${id}/convite`).then((r) => r.data)
export const getMargem = (competencia) =>
  client.get('/relatorios/margem', { params: { competencia } }).then((r) => r.data)
