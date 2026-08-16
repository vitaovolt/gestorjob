import { Navigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { ehSuperAdmin } from '../../utils/format'
import ProtectedRoute from './ProtectedRoute.jsx'

export default function SuperAdminRoute({ children }) {
  const { user } = useAuth()

  return (
    <ProtectedRoute>
      {ehSuperAdmin(user) ? children : <Navigate to="/" replace />}
    </ProtectedRoute>
  )
}
