import { chromium } from '../../code/frontend/node_modules/@playwright/test/index.mjs'
import path from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const htmlPath = path.join(__dirname, 'gestor-job-onboarding.html')
const pdfPath = path.join(__dirname, 'Gestor-Job-Onboarding-e-Testes.pdf')

const browser = await chromium.launch()
const page = await browser.newPage()
await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'networkidle' })
await page.pdf({
  path: pdfPath,
  format: 'A4',
  printBackground: true,
  margin: { top: '12mm', right: '10mm', bottom: '14mm', left: '10mm' },
})
await browser.close()
console.log('PDF gerado:', pdfPath)
