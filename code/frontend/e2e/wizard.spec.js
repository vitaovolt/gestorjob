import { expect, test } from '@playwright/test'
import { entrarComoPlataforma, navPrincipal, sairPeloMenu } from './helpers.js'

test('wizard onboarding após convite: salva serviço e conclui', async ({ page }) => {
  await entrarComoPlataforma(page)
  await page.getByRole('link', { name: '+ Empresa' }).click()
  await page.getByTestId('empresa-nome').fill('Agência Wizard')
  await page.getByTestId('empresa-plano').selectOption('starter')
  await page.getByTestId('empresa-assentos').fill('5')
  await page.getByTestId('empresa-admin-nome').fill('Wiz Admin')
  await page.getByTestId('empresa-admin-email').fill('wiz@agencia.local')
  await page.getByRole('button', { name: 'Criar e enviar convite' }).click()
  await expect(page.getByTestId('convite-url')).toBeVisible()
  const conviteHref = await page.getByTestId('convite-url').getAttribute('href')
  const token = new URL(conviteHref, 'http://127.0.0.1:5173').searchParams.get('token')

  await sairPeloMenu(page)
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()
  await page.goto(`/convite?token=${token}`)
  await expect(page.getByTestId('convite-nome')).toBeVisible()
  await page.getByTestId('convite-nome').fill('Wiz Admin')
  await page.getByTestId('convite-senha').fill('senha-wiz-1')
  await page.getByTestId('convite-senha-confirma').fill('senha-wiz-1')
  await page.getByRole('button', { name: 'Ativar conta e continuar' }).click()

  await expect(page.getByTestId('toast')).toContainText('Conta ativada')
  await expect(page.getByTestId('wizard-root')).toBeVisible()
  await page.getByTestId('wizard-servico-nome').fill('Landing page')
  await page.getByTestId('wizard-continuar').click()
  await expect(page.getByTestId('toast')).toContainText('Serviço cadastrado')

  await page.getByTestId('wizard-pular').click()
  await page.getByTestId('wizard-pular').click()
  await page.getByTestId('wizard-continuar').click()
  await page.getByTestId('wizard-concluir').click()

  await expect(page.getByTestId('toast')).toContainText('Onboarding concluído')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
  await navPrincipal(page).getByRole('link', { name: 'Serviços' }).click()
  await expect(page.getByText('Landing page')).toBeVisible()
})
