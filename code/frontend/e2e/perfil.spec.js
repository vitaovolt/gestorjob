import { expect, test } from '@playwright/test'
import { abrirMenuConta, entrarComo, sairPeloMenu } from './helpers.js'

test('colaborador altera a própria senha em Minha conta', async ({ page }) => {
  await entrarComo(page, 'ana@agenciaeduc.local', 'password')
  await abrirMenuConta(page)
  await page.getByRole('menuitem', { name: 'Minha conta' }).click()
  await expect(page.getByText('Ana Silva')).toBeVisible()

  await page.getByTestId('perfil-senha-atual').fill('password')
  await page.getByTestId('perfil-senha-nova').fill('nova-senha-1')
  await page.getByTestId('perfil-senha-confirma').fill('nova-senha-1')
  await page.getByRole('button', { name: 'Salvar senha' }).click()
  await expect(page.getByTestId('toast')).toContainText('Senha atualizada')

  await sairPeloMenu(page)
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()

  await entrarComo(page, 'ana@agenciaeduc.local', 'nova-senha-1')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
})
