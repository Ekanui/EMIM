import { Link, useNavigate } from 'react-router-dom'
import { ShoppingCart, User, LogOut, Menu, X, Leaf } from 'lucide-react'
import { useState } from 'react'
import useAuthStore from '../store/authStore'
import useCartStore from '../store/cartStore'

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false)
  const { user, logout } = useAuthStore()
  const count = useCartStore((s) => s.count())
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  const isAdmin = user?.role === 'owner' || user?.role === 'employee'

  return (
    <nav className="bg-green-700 text-white shadow-md sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
        <Link to="/" className="flex items-center gap-2 font-bold text-xl">
          <Leaf size={22} />
          Lao Natural
        </Link>

        <div className="hidden md:flex items-center gap-6 text-sm font-medium">
          <Link to="/" className="hover:text-green-200 transition-colors">Home</Link>
          <Link to="/products" className="hover:text-green-200 transition-colors">Products</Link>
          {user && <Link to="/orders" className="hover:text-green-200 transition-colors">My Orders</Link>}
          {isAdmin && <Link to="/admin" className="hover:text-green-200 transition-colors">Admin</Link>}
        </div>

        <div className="hidden md:flex items-center gap-4">
          <Link to="/cart" className="relative hover:text-green-200">
            <ShoppingCart size={22} />
            {count > 0 && (
              <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {count}
              </span>
            )}
          </Link>
          {user ? (
            <div className="flex items-center gap-3">
              <Link to="/profile" className="hover:text-green-200"><User size={20} /></Link>
              <button onClick={handleLogout} className="hover:text-green-200"><LogOut size={20} /></button>
            </div>
          ) : (
            <Link to="/login" className="bg-white text-green-700 px-4 py-1.5 rounded-full text-sm font-semibold hover:bg-green-100 transition-colors">
              Login
            </Link>
          )}
        </div>

        <button className="md:hidden" onClick={() => setMenuOpen(!menuOpen)}>
          {menuOpen ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>

      {menuOpen && (
        <div className="md:hidden bg-green-800 px-4 py-3 flex flex-col gap-3 text-sm font-medium">
          <Link to="/" onClick={() => setMenuOpen(false)}>Home</Link>
          <Link to="/products" onClick={() => setMenuOpen(false)}>Products</Link>
          <Link to="/cart" onClick={() => setMenuOpen(false)}>Cart ({count})</Link>
          {user && <Link to="/orders" onClick={() => setMenuOpen(false)}>My Orders</Link>}
          {user && <Link to="/profile" onClick={() => setMenuOpen(false)}>Profile</Link>}
          {isAdmin && <Link to="/admin" onClick={() => setMenuOpen(false)}>Admin</Link>}
          {user ? (
            <button onClick={() => { handleLogout(); setMenuOpen(false) }} className="text-left">Logout</button>
          ) : (
            <Link to="/login" onClick={() => setMenuOpen(false)}>Login</Link>
          )}
        </div>
      )}
    </nav>
  )
}
