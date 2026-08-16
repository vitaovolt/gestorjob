import { expect, test } from '@playwright/test'
import { entrarComo, navPrincipal } from './helpers.js'

test('visualizador vê clientes sem CTA de criar', async ({ page }) => {
  await entrarComo(page, 'vista@agenciaeduc.local')
  await navPrincipal(page).getByRole('link', { name: 'Clientes' }).click()
  await expect(page.getByTestId('lista-clientes')).toBeVisible()
  await expect(page.getByRole('link', { name: '+ Cliente' })).toHaveCount(0)
  await expect(page.getByRole('link', { name: 'Educ', exact: true })).toHaveCount(0)
  await expect(page.getByTestId('lista-clientes').getByText('Educ', { exact: true })).toBeVisible()
})

test('health da API expõe headers de segurança', async ({ request }) => {
  const res = await request.get('http://127.0.0.1:8000/api/v1/health')
  expect(res.ok()).toBeTruthy()
  expect(res.headers()['x-content-type-options']).toBe('nosniff')
  expect(res.headers()['x-frame-options']).toBe('DENY')
  expect(res.headers()['content-security-policy'] || '').toContain("default-src 'none'")
})
