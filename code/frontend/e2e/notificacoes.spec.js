import { expect, test } from '@playwright/test'
import { entrarComo, entrarComoMariana, navPrincipal } from './helpers.js'

test('mover status via API gera notificação in-app para o colaborador', async ({ page, request }) => {
  await entrarComoMariana(page)
  const token = await page.evaluate(() => localStorage.getItem('gj_token'))
  expect(token).toBeTruthy()

  const lista = await request.get('http://127.0.0.1:8000/api/v1/tarefas', {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  })
  expect(lista.ok()).toBeTruthy()
  const tarefas = await lista.json()
  const reels = (tarefas.data || []).find((t) => t.titulo === 'Reels — Cliente Educ')
  expect(reels?.id).toBeTruthy()

  const alvo = reels.status === 'revisao' ? 'execucao' : 'revisao'
  const put = await request.put(`http://127.0.0.1:8000/api/v1/tarefas/${reels.id}`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    data: { status: alvo },
  })
  expect(put.ok()).toBeTruthy()

  // Garante notificação recente "Movido para …"
  if (alvo === 'execucao') {
    const put2 = await request.put(`http://127.0.0.1:8000/api/v1/tarefas/${reels.id}`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      data: { status: 'revisao' },
    })
    expect(put2.ok()).toBeTruthy()
  }

  await page.getByRole('button', { name: 'Sair' }).click()
  await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible()
  await entrarComo(page, 'ana@agenciaeduc.local', 'password')
  await expect(page.getByTestId('btn-notif')).toBeVisible()
  await expect(page.getByTestId('notif-badge')).toBeVisible()

  await page.getByTestId('btn-notif').click()
  await expect(page.getByTestId('notif-panel')).toBeVisible()
  await expect(page.getByTestId('notif-panel')).toContainText('Movido para Em revisão')
  await expect(page.getByTestId('notif-panel')).toContainText('Reels — Cliente Educ')

  await page.getByTestId('notif-ler-todas').click()
  await expect(page.getByTestId('toast')).toContainText('Todas marcadas como lidas')
  await expect(page.getByTestId('notif-badge')).toHaveCount(0)
  await expect(navPrincipal(page).getByRole('link', { name: 'Kanban' })).toBeVisible()
})
