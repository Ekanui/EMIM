import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Navbar from './components/Navbar'
import Footer from './components/Footer'
import ProtectedRoute from './components/ProtectedRoute'
import AdminLayout from './components/admin/AdminLayout'

import Home from './pages/Home'
import Products from './pages/Products'
import ProductDetail from './pages/ProductDetail'
import Cart from './pages/Cart'
import Checkout from './pages/Checkout'
import Orders from './pages/Orders'
import Profile from './pages/Profile'
import Login from './pages/Login'
import Register from './pages/Register'
import NotFound from './pages/NotFound'

import AdminDashboard from './pages/admin/AdminDashboard'
import AdminProducts from './pages/admin/AdminProducts'
import AdminCategories from './pages/admin/AdminCategories'
import AdminOrders from './pages/admin/AdminOrders'
import AdminUsers from './pages/admin/AdminUsers'
import AdminBanners from './pages/admin/AdminBanners'
import AdminSettings from './pages/admin/AdminSettings'

function PublicLayout({ children }) {
  return (
    <div className="flex flex-col min-h-screen bg-gray-50">
      <Navbar />
      <main className="flex-1">{children}</main>
      <Footer />
    </div>
  )
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public routes */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        {/* Customer routes */}
        <Route path="/" element={<PublicLayout><Home /></PublicLayout>} />
        <Route path="/products" element={<PublicLayout><Products /></PublicLayout>} />
        <Route path="/products/:id" element={<PublicLayout><ProductDetail /></PublicLayout>} />
        <Route path="/cart" element={<PublicLayout><Cart /></PublicLayout>} />
        <Route path="/checkout" element={
          <PublicLayout>
            <ProtectedRoute roles={['user', 'owner', 'employee']}>
              <Checkout />
            </ProtectedRoute>
          </PublicLayout>
        } />
        <Route path="/orders" element={
          <PublicLayout>
            <ProtectedRoute roles={['user', 'owner', 'employee']}>
              <Orders />
            </ProtectedRoute>
          </PublicLayout>
        } />
        <Route path="/profile" element={
          <PublicLayout>
            <ProtectedRoute roles={['user', 'owner', 'employee']}>
              <Profile />
            </ProtectedRoute>
          </PublicLayout>
        } />

        {/* Admin routes */}
        <Route path="/admin" element={
          <ProtectedRoute roles={['owner', 'employee']}>
            <AdminLayout />
          </ProtectedRoute>
        }>
          <Route index element={<AdminDashboard />} />
          <Route path="products" element={<AdminProducts />} />
          <Route path="categories" element={<AdminCategories />} />
          <Route path="orders" element={<AdminOrders />} />
          <Route path="users" element={
            <ProtectedRoute roles={['owner']}>
              <AdminUsers />
            </ProtectedRoute>
          } />
          <Route path="banners" element={<AdminBanners />} />
          <Route path="settings" element={
            <ProtectedRoute roles={['owner']}>
              <AdminSettings />
            </ProtectedRoute>
          } />
        </Route>

        <Route path="*" element={<PublicLayout><NotFound /></PublicLayout>} />
      </Routes>
    </BrowserRouter>
  )
}
