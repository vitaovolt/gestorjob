import { expect, test } from '@playwright/test'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { entrarComoMariana } from './helpers.js'

const fixture = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures', 'briefing.png')

const FORA_DA_LISTA = [
  { name: 'lixo.bin', mimeType: 'application/octet-stream' },
  { name: 'notas.txt', mimeType: 'text/plain' },
  { name: 'setup.exe', mimeType: 'application/x-msdownload' },
  { name: 'foto.bmp', mimeType: 'image/bmp' },
]

test('anexa arquivo no drawer da tarefa e remove', async ({ page }) => {
  await entrarComoMariana(page)
  await page.getByTestId('coluna-execucao').getByText('Reels — Cliente Educ').click()
  await expect(page.getByTestId('drawer-root')).toBeVisible()
  await expect(page.getByTestId('lista-anexos')).toContainText('Nenhum arquivo ainda.')

  await page.getByTestId('anexo-arquivo').setInputFiles(fixture)
  await expect(page.getByTestId('toast')).toContainText('Arquivo anexado')
  await expect(page.getByTestId('lista-anexos').getByText('briefing.png')).toBeVisible()

  const downloadPromise = page.waitForEvent('download')
  await page.getByTestId('lista-anexos').getByRole('button', { name: 'Baixar' }).click()
  const download = await downloadPromise
  expect(download.suggestedFilename()).toBe('briefing.png')

  await page.getByTestId('lista-anexos').getByRole('button', { name: 'Excluir' }).click()
  await page.getByTestId('confirmar-excluir-anexo').click()
  await expect(page.getByTestId('toast')).toContainText('Anexo removido')
  await expect(page.getByTestId('lista-anexos')).toContainText('Nenhum arquivo ainda.')
})

test('rejeita qualquer arquivo fora da allowlist', async ({ page }) => {
  await entrarComoMariana(page)
  await page.getByTestId('coluna-execucao').getByText('Reels — Cliente Educ').click()
  await expect(page.getByTestId('drawer-root')).toBeVisible()

  for (const arquivo of FORA_DA_LISTA) {
    await page.getByTestId('anexo-arquivo').setInputFiles({
      name: arquivo.name,
      mimeType: arquivo.mimeType,
      buffer: Buffer.alloc(2048),
    })
    await expect(page.getByTestId('toast')).toContainText('Arquivo fora da lista permitida')
    await expect(page.getByTestId('enviar-anexo')).toHaveText('Enviar arquivo')
    await expect(page.getByTestId('lista-anexos')).toContainText('Nenhum arquivo ainda.')
  }
})
