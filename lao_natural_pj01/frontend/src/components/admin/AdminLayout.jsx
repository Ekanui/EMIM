import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { LayoutDashboard, Package, Tag, ShoppingBag, Users, Image, Settings, LogOut, Leaf } from 'lucide-react'
import useAuthStore from '../../store/authStore'

const links = [
  { to: '/admin', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/admin/products', label: 'Products', icon: Package },
  { to: '/admin/categories', label: 'Categories', icon: Tag },
  { to: '/admin/orders', label: 'Orders', icon: ShoppingBag },
  { to: '/admin/users', label: 'Users', icon: Users, ownerOnly: true },
  { to: '/admin/banners', label: 'Banners', icon: Image },
  { to: '/admin/settings', label: 'Settings', icon: Settings, ownerOnly: true },
]

export default function AdminLayout() {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  return (
    <div className="flex min-h-screen bg-gray-100">
      <aside className="w-56 bg-green-800 text-white flex flex-col shrink-0">
        <div className="flex items-center gap-2 px-4 py-5 font-bold text-lg border-b border-green-700">
          <Leaf size={20} />
          Admin Panel
        </div>
        <nav className="flex-1 py-4 flex flex-col gap-1 px-2">
          {links.map(({ to, label, icon: Icon, end, ownerOnly }) => {
            if (ownerOnly && user?.role !== 'owner') return null
            return (
              <NavLink
                key={to}
                to={to}
                end={end}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive ? 'bg-green-600' : 'hover:bg-green-700'
                  }`
                }
              >
                <Icon size={18} />
                {label}
              </NavLink>
            )
          })}
        </nav>
        <div className="px-4 py-4 border-t border-green-700">
          <p className="text-xs text-green-300 mb-1">{user?.name}</p>
          <p className="text-xs text-green-400 capitalize mb-3">{user?.role}</p>
          <button onClick={handleLogout} className="flex items-center gap-2 text-sm hover:text-red-300 transition-colors">
            <LogOut size={16} /> Logout
          </button>
        </div>
      </aside>
      <main className="flex-1 p-6 overflow-auto">
        <Outlet />
      </main>
    </div>
  )
}
