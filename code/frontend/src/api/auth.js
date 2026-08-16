import client from './client'

export async function login(email, password, deviceName = 'spa') {
  const { data } = await client.post('/auth/login', {
    email,
    password,
    device_name: deviceName,
  })
  return data
}

export async function logout() {
  const { data } = await client.post('/auth/logout')
  return data
}

export async function fetchMe() {
  const { data } = await client.get('/auth/me')
  return data
}

export async function refreshToken() {
  const { data } = await client.post('/auth/refresh')
  return data
}

export async function alterarSenha(senhaAtual, password, passwordConfirmation) {
  const { data } = await client.post('/auth/senha', {
    senha_atual: senhaAtual,
    password,
    password_confirmation: passwordConfirmation,
  })
  return data
}

export async function solicitarRecuperacaoSenha(email) {
  const { data } = await client.post('/auth/recuperar-senha', { email })
  return data
}

export async function redefinirSenha(token, password, passwordConfirmation) {
  const { data } = await client.post('/auth/redefinir-senha', {
    token,
    password,
    password_confirmation: passwordConfirmation,
  })
  return data
}

export async function statusWizard() {
  const { data } = await client.get('/wizard')
  return data
}

export async function concluirWizard() {
  const { data } = await client.post('/wizard/concluir')
  return data
}

export async function previewConvite(token) {
  const { data } = await client.get(`/convites/${encodeURIComponent(token)}`)
  return data
}

export async function aceitarConvite(token, payload) {
  const { data } = await client.post(`/convites/${encodeURIComponent(token)}`, payload)
  return data
}
