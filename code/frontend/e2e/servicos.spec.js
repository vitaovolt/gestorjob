import { expect, test } from '@playwright/test'
import { entrarComoMariana, navPrincipal, editarNaLista, excluirNaLista } from './helpers.js'

test('CRUD de serviço na tela', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Serviços' }).click()

  await expect(page.getByTestId('lista-servicos')).toBeVisible()
  await expect(page.getByRole('link', { name: 'Reels Instagram', exact: true })).toBeVisible()

  await page.getByRole('link', { name: '+ Serviço' }).click()
  await page.getByTestId('servico-nome').fill('Landing page')
  await page.getByTestId('servico-preco').fill('')
  await page.getByTestId('servico-preco').pressSequentially('250000')
  await expect(page.getByTestId('servico-preco')).toHaveValue('2.500,00')
  await page.getByTestId('servico-tempo').fill('240')
  await page.getByTestId('servico-frequencia').selectOption('semanal')
  await page.getByTestId('servico-checklist').fill('Briefing\nArte\nPublicar')
  await page.getByRole('button', { name: 'Salvar serviço' }).click()

  await expect(page.getByTestId('toast')).toContainText('Serviço criado')
  await expect(page.getByTestId('lista-servicos').getByText('Landing page')).toBeVisible()
  await expect(editarNaLista(page, 'lista-servicos', 'Landing page')).toBeVisible()

  await editarNaLista(page, 'lista-servicos', 'Landing page').click()
  await expect(page.getByTestId('servico-nome')).toHaveValue('Landing page')
  await expect(page.getByTestId('servico-preco')).toHaveValue('2.500,00')
  await page.getByTestId('servico-preco').fill('')
  await page.getByTestId('servico-preco').pressSequentially('280000')
  await expect(page.getByTestId('servico-preco')).toHaveValue('2.800,00')
  await page.getByRole('button', { name: 'Salvar alterações' }).click()
  await expect(page.getByTestId('toast')).toContainText('Serviço atualizado')
  await expect(page.getByTestId('lista-servicos').getByText(/R\$\s*2\.800,00/)).toBeVisible()

  await excluirNaLista(page, 'lista-servicos', 'Landing page').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText('Serviço removido')
  await expect(page.getByTestId('lista-servicos').getByText('Landing page')).toHaveCount(0)

  await excluirNaLista(page, 'lista-servicos', 'Reels Instagram').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText('tarefas')
  await expect(page.getByRole('link', { name: 'Reels Instagram', exact: true })).toBeVisible()
})
