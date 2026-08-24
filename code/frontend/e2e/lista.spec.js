import { expect, test } from '@playwright/test'
import { entrarComoMariana, navPrincipal } from './helpers.js'

test('lista de tarefas abre o drawer', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Lista' }).click()

  await expect(page.getByTestId('lista-tarefas')).toBeVisible()
  await expect(page.getByTestId('lista-tarefas').getByText('Reels — Cliente Educ')).toBeVisible()

  await page.getByTestId('lista-tarefas').getByText('Reels — Cliente Educ').click()
  await expect(page.getByTestId('drawer-root')).toBeVisible()
  await expect(page.getByTestId('timer-display')).toBeVisible()
})

test('linha atrasada tem fundo vermelho suave e filtro Atrasadas', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Lista' }).click()

  const atrasada = page.getByTestId('lista-tarefas').locator('tr', { hasText: 'Copy atrasada Educ' })
  await expect(atrasada).toBeVisible()
  await expect(atrasada).toHaveAttribute('data-atrasada', 'sim')

  await page.getByTestId('lista-visao-atrasadas').click()
  await expect(page.getByTestId('lista-tarefas').getByText('Copy atrasada Educ')).toBeVisible()
  await expect(page.getByTestId('lista-tarefas').getByText('Reels — Cliente Educ')).toHaveCount(0)
})
