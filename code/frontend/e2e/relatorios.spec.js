import { expect, test } from '@playwright/test'
import { entrarComo, entrarComoMariana, navPrincipal } from './helpers.js'

test('admin vê inteligência, dashboard e margem', async ({ page }) => {
  await entrarComoMariana(page)

  const nav = navPrincipal(page)
  await expect(nav.getByText('Inteligência')).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Dashboard' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Margem' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Atrasos' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Horas' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Carga' })).toBeVisible()

  await nav.getByRole('link', { name: 'Dashboard' }).click()
  await expect(page.getByTestId('dashboard-page')).toBeVisible()
  await expect(page.getByText(/Não foi possível carregar o dashboard/)).toHaveCount(0)
  await expect(page.getByTestId('dash-stat-atrasadas')).toBeVisible()

  await nav.getByRole('link', { name: 'Margem' }).click()
  await expect(page.getByTestId('relatorio-margem')).toBeVisible()
  await expect(page.getByTestId('relatorio-margem').getByText('Educ')).toBeVisible()

  await nav.getByRole('link', { name: 'Atrasos' }).click()
  await expect(page.getByTestId('relatorio-atrasos')).toBeVisible()
  await expect(page.getByTestId('relatorio-atrasos').getByText('Copy atrasada Educ')).toBeVisible()
})

test('colaborador não vê relatórios e é redirecionado', async ({ page }) => {
  await entrarComo(page, 'ana@agenciaeduc.local')

  const nav = navPrincipal(page)
  await expect(nav.getByText('Inteligência')).toHaveCount(0)
  await expect(nav.getByRole('link', { name: 'Dashboard' })).toHaveCount(0)
  await expect(nav.getByRole('link', { name: 'Margem' })).toHaveCount(0)

  await page.goto('/relatorios/margem')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
  await expect(page.getByTestId('relatorio-margem')).toHaveCount(0)
})
