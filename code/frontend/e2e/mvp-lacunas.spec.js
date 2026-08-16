import { expect, test } from '@playwright/test'
import { editarNaLista, entrarComoMariana, navPrincipal } from './helpers'

test('recorrência gera cards e drawer mostra custo/comentário', async ({ page }) => {
  await entrarComoMariana(page)

  await navPrincipal(page).getByRole('link', { name: 'Serviços' }).click()
  await expect(page.getByTestId('lista-servicos')).toBeVisible()
  await editarNaLista(page, 'lista-servicos', 'Post feed').click()
  await expect(page.getByTestId('painel-recorrencia')).toBeVisible()

  await page.getByTestId('recorrencia-cliente').selectOption({ label: 'Educ' })
  const titulo = page.getByTestId('recorrencia-titulo')
  await titulo.click()
  await titulo.fill('')
  await titulo.pressSequentially('IG 3x Educ e2e', { delay: 20 })

  await page.getByTestId('recorrencia-ativar').click()
  await expect(page.getByTestId('toast')).toBeVisible()
  await expect(page.getByTestId('toast')).toContainText(/Recorrência ativa|card/)
  await expect(page.getByTestId('lista-recorrencias')).toBeVisible()

  await navPrincipal(page).getByRole('link', { name: 'Kanban' }).click()
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
