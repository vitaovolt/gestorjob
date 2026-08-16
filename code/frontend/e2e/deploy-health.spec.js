import { expect, test } from '@playwright/test'

test('health local responde ok com check de database', async ({ request }) => {
  const res = await request.get('http://127.0.0.1:8000/api/v1/health')
  expect(res.ok()).toBeTruthy()
  const body = await res.json()
  expect(body.success).toBeTruthy()
  expect(body.data.status).toBe('ok')
  expect(body.data.service).toBe('gestor-job-api')
  expect(body.data.checks.database).toBe('ok')
  expect(res.headers()['x-frame-options']).toBe('DENY')
})
