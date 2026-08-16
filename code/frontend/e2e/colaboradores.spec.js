import { expect, test } from '@playwright/test'
import { entrarComoMariana, navPrincipal, editarNaLista, excluirNaLista } from './helpers.js'

test('CRUD de colaborador na tela', async ({ page }) => {
  await entrarComoMariana(page)
  await navPrincipal(page).getByRole('link', { name: 'Colaboradores' }).click()

  await expect(page.getByTestId('lista-colaboradores')).toBeVisible()
  await expect(page.getByRole('link', { name: 'Ana Silva', exact: true })).toBeVisible()

  await page.getByRole('link', { name: '+ Colaborador' }).click()
  await page.getByTestId('colaborador-nome').fill('Bruno Lima')
  await page.getByTestId('colaborador-email').fill('bruno@agenciaeduc.local')
  await page.getByTestId('colaborador-custo').fill('')
  await page.getByTestId('colaborador-custo').pressSequentially('5500')
  await expect(page.getByTestId('colaborador-custo')).toHaveValue('55,00')
  await page.getByTestId('colaborador-senha').fill('senha1234')
  await page.getByTestId('colaborador-senha-confirma').fill('senha1234')
  await page.getByRole('button', { name: 'Salvar colaborador' }).click()

  await expect(page.getByTestId('toast')).toContainText('Colaborador criado')
  await expect(page.getByTestId('lista-colaboradores').getByText('Bruno Lima')).toBeVisible()
  await expect(editarNaLista(page, 'lista-colaboradores', 'Bruno Lima')).toBeVisible()

  await editarNaLista(page, 'lista-colaboradores', 'Bruno Lima').click()
  await expect(page.getByTestId('colaborador-nome')).toHaveValue('Bruno Lima')
  await expect(page.getByTestId('colaborador-custo')).toHaveValue('55,00')
  await page.getByTestId('colaborador-custo').fill('')
  await page.getByTestId('colaborador-custo').pressSequentially('8000')
  await expect(page.getByTestId('colaborador-custo')).toHaveValue('80,00')
  await page.getByRole('button', { name: 'Salvar alterações' }).click()
  await expect(page.getByTestId('toast')).toContainText('Colaborador atualizado')
  await expect(page.getByTestId('lista-colaboradores').getByText(/R\$\s*80,00/)).toBeVisible()
  await expect(excluirNaLista(page, 'lista-colaboradores', 'Mariana Costa')).toHaveCount(0)

  await excluirNaLista(page, 'lista-colaboradores', 'Bruno Lima').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText('Colaborador removido')
  await expect(page.getByTestId('lista-colaboradores').getByText('Bruno Lima')).toHaveCount(0)

  await excluirNaLista(page, 'lista-colaboradores', 'Ana Silva').click()
  await page.getByTestId('confirmar-excluir').click()
  await expect(page.getByTestId('toast')).toContainText(/tarefas|horas/)
  await expect(page.getByRole('link', { name: 'Ana Silva', exact: true })).toBeVisible()
})
