import { expect, test } from '@playwright/test'
import { entrarComo, entrarComoMariana, navPrincipal } from './helpers.js'

test('admin salva config e vê a matriz de permissões', async ({ page }) => {
  await entrarComoMariana(page)
  await expect(navPrincipal(page).getByRole('link', { name: 'Configurações' })).toBeVisible()
  await expect(navPrincipal(page).getByRole('link', { name: 'Permissões' })).toBeVisible()

  await navPrincipal(page).getByRole('link', { name: 'Configurações' }).click()
  await expect(page.getByTestId('form-config')).toBeVisible()
  await expect(page.getByText(/Não foi possível carregar as configurações/)).toHaveCount(0)
  await expect(page.getByTestId('config-digest-diario')).toBeEnabled()
  await expect(page.getByTestId('config-digest-diario')).not.toBeChecked()
  await expect(page.getByText(/Prazo hoje: e-mail para os responsáveis/)).toBeVisible()

  await page.getByTestId('config-digest-diario').check()
  await page.getByRole('button', { name: 'Salvar configurações' }).click()
  await expect(page.getByTestId('toast')).toContainText('Configurações salvas')

  await page.reload()
  await expect(page.getByTestId('config-digest-diario')).toBeChecked()

  await navPrincipal(page).getByRole('link', { name: 'Permissões' }).click()
  await expect(page.getByTestId('matriz-permissoes')).toBeVisible()
  await expect(page.getByTestId('matriz-permissoes').getByText('Criar tarefas')).toBeVisible()
  await expect(page.getByTestId('matriz-permissoes').getByText('Config. (Não)').first()).toBeVisible()
})

test('colaborador não vê Configurações', async ({ page }) => {
  await entrarComo(page, 'ana@agenciaeduc.local')
  await expect(navPrincipal(page).getByRole('link', { name: 'Configurações' })).toHaveCount(0)
  await expect(navPrincipal(page).getByRole('link', { name: 'Permissões' })).toHaveCount(0)

  await page.goto('/config')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
})
