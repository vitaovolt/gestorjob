import { expect, test } from '@playwright/test'
import { entrarComo } from './helpers.js'

test('recupera senha pelo link local e entra com a nova', async ({ page }) => {
  // Usuário do seed que os outros specs não usam (evita quebrar a suíte ao trocar a senha).
  const email = 'ops@studionorte.local'

  await page.goto('/login')
  await page.getByTestId('link-esqueci-senha').click()
  await expect(page.getByTestId('form-recuperar')).toBeVisible()

  await page.getByTestId('recuperar-email').fill(email)
  await page.getByTestId('recuperar-enviar').click()
  await expect(page.getByTestId('recuperar-ok')).toBeVisible()

  const resetLink = page.getByTestId('recuperar-reset-url')
  await expect(resetLink).toBeVisible()
  const href = await resetLink.getAttribute('href')
  expect(href).toContain('/redefinir-senha?token=')

  await page.goto(href)
  await expect(page.getByTestId('form-redefinir')).toBeVisible()
  await page.getByTestId('redefinir-senha').fill('senha-recup-1')
  await page.getByTestId('redefinir-senha-confirma').fill('senha-recup-1')
  await page.getByTestId('redefinir-enviar').click()
  await expect(page.getByTestId('toast')).toContainText('Senha redefinida')
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()

  await entrarComo(page, email, 'senha-recup-1')
  await expect(page.getByTestId('kanban-board')).toBeVisible()
})

test('e-mail inexistente mostra erro e não abre Link enviado', async ({ page }) => {
  await page.goto('/recuperar')
  await page.getByTestId('recuperar-email').fill('naoexiste@exemplo.local')
  await page.getByTestId('recuperar-enviar').click()
  await expect(page.getByRole('alert')).toContainText('Este e-mail não está cadastrado.')
  await expect(page.getByTestId('form-recuperar')).toBeVisible()
  await expect(page.getByTestId('recuperar-ok')).toHaveCount(0)
  await expect(page.getByTestId('recuperar-enviar')).toHaveText('Enviar link')
})
