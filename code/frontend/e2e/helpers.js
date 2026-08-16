import { expect } from '@playwright/test'

export async function entrarComo(page, email, senha = 'password', destino = 'kanban-board') {
  await page.goto('/login')
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()
  await page.getByLabel('E-mail').fill(email)
  await expect(page.getByLabel('E-mail')).toHaveValue(email)
  await page.getByLabel('Senha').fill(senha)
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByTestId(destino)).toBeVisible()

  // Recupera de GET falho pontual (serve reiniciando / schema no ar).
  for (let i = 0; i < 3; i += 1) {
    const falhaApi = page.getByText(/Não foi possível carregar/)
    if ((await falhaApi.count()) === 0) return
    await page.reload()
    await expect(page.getByTestId(destino)).toBeVisible()
  }
  await expect(page.getByText(/Não foi possível carregar/)).toHaveCount(0)
}

export async function entrarComoMariana(page) {
  await entrarComo(page, 'mariana@agenciaeduc.local', 'password')
}

export async function entrarComoPlataforma(page) {
  await entrarComo(page, 'plataforma@gestorjob.local', 'password', 'lista-empresas')
}

export function navPrincipal(page) {
  return page.getByRole('navigation', { name: 'Principal' })
}

export async function abrirMenuConta(page) {
  await page.getByTestId('btn-conta-menu').click()
  await expect(page.getByTestId('conta-menu-panel')).toBeVisible()
}

export async function sairPeloMenu(page) {
  await abrirMenuConta(page)
  await page.getByRole('menuitem', { name: 'Sair' }).click()
}

export function editarNaLista(page, listaTestId, textoLinha) {
  return page.getByTestId(listaTestId).locator('tr', { hasText: textoLinha }).getByRole('link', { name: 'Editar' })
}

export function excluirNaLista(page, listaTestId, textoLinha) {
  return page.getByTestId(listaTestId).locator('tr', { hasText: textoLinha }).getByRole('button', { name: 'Excluir' })
}
