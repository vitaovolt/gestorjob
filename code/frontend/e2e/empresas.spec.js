import { expect, test } from '@playwright/test'
import { entrarComoPlataforma, navPrincipal, editarNaLista, sairPeloMenu } from './helpers.js'

test('Super Admin cria empresa e admin ativa o convite', async ({ page }) => {
  await entrarComoPlataforma(page)
  await expect(navPrincipal(page).getByRole('link', { name: 'Empresas' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Agência Educ', exact: true })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Studio Norte', exact: true })).toBeVisible()

  await page.getByRole('link', { name: '+ Empresa' }).click()
  await page.getByTestId('empresa-nome').fill('Agência Pixel')
  await page.getByTestId('empresa-plano').selectOption('pro')
  await page.getByTestId('empresa-assentos').fill('8')
  await page.getByTestId('empresa-admin-nome').fill('Lia Pixel')
  await page.getByTestId('empresa-admin-email').fill('lia@pixel.local')
  await page.getByRole('button', { name: 'Criar e enviar convite' }).click()

  await expect(page.getByTestId('toast')).toContainText('Empresa criada')
  await expect(page.getByTestId('convite-url')).toBeVisible()

  const conviteHref = await page.getByTestId('convite-url').getAttribute('href')
  expect(conviteHref).toContain('/convite?token=')
  const token = new URL(conviteHref, 'http://127.0.0.1:5173').searchParams.get('token')
  expect(token).toBeTruthy()

  await page.getByRole('link', { name: 'Voltar' }).click()
  await expect(page.getByTestId('lista-empresas').getByText('Agência Pixel')).toBeVisible()
  await expect(editarNaLista(page, 'lista-empresas', 'Agência Pixel')).toBeVisible()

  await sairPeloMenu(page)
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()

  await page.goto(`/convite?token=${token}`)
  await expect(page.getByText('Agência Pixel')).toBeVisible()
  await page.getByTestId('convite-nome').fill('Lia Pixel')
  await page.getByTestId('convite-senha').fill('senha-lia-1')
  await page.getByTestId('convite-senha-confirma').fill('senha-lia-1')
  await page.getByRole('button', { name: 'Ativar conta e continuar' }).click()

  await expect(page.getByTestId('toast')).toContainText('Conta ativada')
  await expect(page.getByTestId('wizard-root')).toBeVisible()
  await expect(page.getByTestId('wizard-passos')).toContainText('Serviços')

  for (let i = 0; i < 4; i += 1) {
    await page.getByTestId('wizard-pular').click()
  }
  await page.getByTestId('wizard-concluir').click()
  await expect(page.getByTestId('toast')).toContainText('Onboarding concluído')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
  await expect(navPrincipal(page).getByRole('link', { name: 'Clientes' })).toBeVisible()
})
