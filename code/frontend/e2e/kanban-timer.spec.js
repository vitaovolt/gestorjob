import { expect, test } from '@playwright/test'
import { entrarComoMariana, navPrincipal } from './helpers.js'

test('login → arrastar card → timer visível → pausar', async ({ page }) => {
  // Sempre Mariana: o spec de empresas deixa a sessão em outro tenant (Kanban vazio).
  await entrarComoMariana(page)

  const card = page.getByTestId('coluna-execucao').getByText('Reels — Cliente Educ')
  await expect(card).toBeVisible()

  await card.dragTo(page.getByTestId('coluna-revisao'))
  await expect(page.getByTestId('coluna-revisao').getByText('Reels — Cliente Educ')).toBeVisible()

  await page.getByTestId('coluna-revisao').getByText('Reels — Cliente Educ').click()
  await expect(page.getByTestId('drawer-root')).toBeVisible()
  await expect(page.getByTestId('timer-display')).toContainText('Pausado')
  await expect(page.getByTestId('timer-play')).toBeVisible()

  await page.getByTestId('fase-producao').click()
  await expect(page.getByTestId('timer-display')).toContainText('Rodando')
  await expect(page.getByTestId('timer-pill')).toBeVisible()
  const toast = page.getByTestId('toast')
  await expect(toast).toContainText('Timer em andamento')
  const toastBox = await toast.boundingBox()
  const ctaBox = await page.getByRole('link', { name: '+ Tarefa' }).boundingBox()
  expect(toastBox).toBeTruthy()
  expect(ctaBox).toBeTruthy()
  expect(toastBox.y).toBeGreaterThan(ctaBox.y + ctaBox.height - 1)
  const hit = await page.evaluate(({ x, y }) => {
    const el = document.elementFromPoint(x, y)
    return Boolean(el?.closest('[data-testid="toast"]'))
  }, { x: toastBox.x + toastBox.width / 2, y: toastBox.y + toastBox.height / 2 })
  expect(hit).toBe(true)

  await page.getByTestId('timer-pausar').click()
  await expect(page.getByTestId('toast')).toContainText('Timer pausado')
  await expect(page.getByTestId('timer-display')).toContainText('Pausado')
  await expect(page.getByTestId('timer-play')).toBeVisible()

  await page.getByTestId('timer-play').click()
  await expect(page.getByTestId('timer-display')).toContainText('Rodando')
  await expect(page.getByTestId('toast')).toContainText('Timer em andamento')
})

test('timer inicia ao abrir o card quando a flag está ligada', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Configurações' }).click()
  await page.getByTestId('config-timer-ao-abrir').check()
  await page.getByRole('button', { name: 'Salvar configurações' }).click()
  await expect(page.getByTestId('toast')).toContainText('Configurações salvas')

  try {
    await navPrincipal(page).getByRole('link', { name: 'Kanban' }).click()
    await page.getByText('Reels — Cliente Educ').click()
    await expect(page.getByTestId('drawer-root')).toBeVisible()
    await expect(page.getByTestId('timer-display')).toContainText('Rodando')
    await page.getByTestId('timer-pausar').click()
    await expect(page.getByTestId('timer-display')).toContainText('Pausado')
    await page.getByLabel('Fechar drawer').click()
  } finally {
    await navPrincipal(page).getByRole('link', { name: 'Configurações' }).click()
    await page.getByTestId('config-timer-ao-abrir').uncheck()
    await page.getByRole('button', { name: 'Salvar configurações' }).click()
    await expect(page.getByTestId('toast')).toContainText('Configurações salvas')
  }
})
