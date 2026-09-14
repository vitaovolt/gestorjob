import { Navigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import ProtectedRoute from './ProtectedRoute.jsx'

export default function FinanceiroRoute({ children }) {
  const { user } = useAuth()

  return (
    <ProtectedRoute>
      {user?.permissoes?.ver_financeiro ? children : <Navigate to="/" replace />}
    </ProtectedRoute>
  )
}
