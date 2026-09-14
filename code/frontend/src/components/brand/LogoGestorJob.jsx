export default function LogoGestorJob({ className = 'h-10 w-auto', variante = 'claro' }) {
  const src = variante === 'escuro' ? '/logo-gestorjob-branco.png' : '/logo-gestorjob.png'

  return <img src={src} alt="Gestor Job" className={className} />
}
