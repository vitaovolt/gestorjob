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
