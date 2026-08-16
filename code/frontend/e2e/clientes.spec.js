import { expect, test } from '@playwright/test'
import { entrarComoMariana, navPrincipal, editarNaLista, excluirNaLista } from './helpers.js'

test('CRUD de cliente na tela', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Clientes' }).click()

  await expect(page.getByTestId('lista-clientes')).toBeVisible()
  await expect(page.getByRole('link', { name: 'Educ', exact: true })).toBeVisible()

  await page.getByRole('link', { name: '+ Cliente' }).click()
  await page.getByTestId('cliente-nome').fill('Studio Pixel')
  await page.getByTestId('cliente-cnpj').fill('12ABC34501DE35')
  await expect(page.getByTestId('cliente-cnpj')).toHaveValue('12.ABC.345/01DE-35')
  await page.getByTestId('cliente-inicio-cal').fill('2026-08-14')
  await expect(page.getByTestId('cliente-inicio')).toHaveValue('14/08/2026')
  await page.getByTestId('cliente-fee').fill('')
  await page.getByTestId('cliente-fee').pressSequentially('150000')
  await expect(page.getByTestId('cliente-fee')).toHaveValue('1.500,00')
  await page.getByRole('button', { name: 'Salvar cliente' }).click()

  await expect(page.getByTestId('toast')).toContainText('Cliente criado')
  await expect(page.getByTestId('lista-clientes').getByText('Studio Pixel')).toBeVisible()
  await expect(editarNaLista(page, 'lista-clientes', 'Studio Pixel')).toBeVisible()

  await editarNaLista(page, 'lista-clientes', 'Studio Pixel').click()
  await expect(page.getByTestId('cliente-nome')).toHaveValue('Studio Pixel')
  await expect(page.getByTestId('cliente-fee')).toHaveValue('1.500,00')
  const fee = page.getByTestId('cliente-fee')
  await fee.click()
  await fee.press('Control+A')
  await fee.pressSequentially('180000', { delay: 15 })
  await fee.blur()
  await expect(fee).toHaveValue('1.800,00')
  await page.getByRole('button', { name: 'Salvar alterações' }).click()
  await expect(page.getByTestId('toast')).toContainText('Cliente atualizado')
  await expect(page.getByTestId('lista-clientes').locator('tr', { hasText: 'Studio Pixel' })).toContainText(/1\.800,00/)

  await excluirNaLista(page, 'lista-clientes', 'Studio Pixel').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText('Cliente removido')
  await expect(page.getByTestId('lista-clientes').getByText('Studio Pixel')).toHaveCount(0)

  await excluirNaLista(page, 'lista-clientes', 'Educ').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText('tarefas')
  await expect(page.getByRole('link', { name: 'Educ', exact: true })).toBeVisible()
})
