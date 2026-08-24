import { expect, test } from '@playwright/test'
import { editarNaLista, entrarComoMariana, navPrincipal } from './helpers'

test('nova tarefa com repetição gera cards e drawer mostra custo/comentário', async ({ page }) => {
  await entrarComoMariana(page)

  await page.getByRole('link', { name: '+ Tarefa' }).click()
  await expect(page.getByTestId('form-nova-tarefa')).toBeVisible()
  await expect(page.getByTestId('tarefa-salvar')).toHaveCount(2)
  await page.getByTestId('tarefa-titulo').fill('IG 3x Educ e2e')
  await page.getByTestId('tarefa-cliente').selectOption({ label: 'Educ' })
  await page.getByTestId('tarefa-repeticao').selectOption('semanal')
  await page.getByText('Ter', { exact: true }).click()
  await page.getByText('Qua', { exact: true }).click()
  await page.getByTestId('tarefa-salvar').first().click()
  await expect(page.getByTestId('toast')).toContainText(/Tarefa criada/)
  await expect(page.getByTestId('kanban-board')).toBeVisible()
  await expect(page.getByText('IG 3x Educ e2e').first()).toBeVisible()

  await page.getByText('IG 3x Educ e2e').first().click()
  await expect(page.getByTestId('drawer-root')).toBeVisible()
  await expect(page.getByTestId('custo-acumulado')).toBeVisible()
  await expect(page.getByTestId('timeline-comentarios')).toBeVisible()

  await page.getByTestId('comentario-input').click()
  await page.getByTestId('comentario-input').pressSequentially('Arte v1 no Figma', { delay: 15 })
  await page.getByTestId('comentario-enviar').click()
  await expect(page.getByTestId('timeline-comentarios')).toContainText('Arte v1 no Figma')
})

test('serviço é catálogo sem painel de recorrência', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Serviços' }).click()
  await editarNaLista(page, 'lista-servicos', 'Post feed').click()
  await expect(page.getByTestId('servico-nome')).toBeVisible()
  await expect(page.getByTestId('painel-recorrencia')).toHaveCount(0)
  await expect(page.getByTestId('servico-frequencia')).toHaveCount(0)
})
