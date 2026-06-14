import { useEffect, useState } from 'react'
import { TrendingUp, ShoppingBag, Users, Package, AlertTriangle } from 'lucide-react'
import api from '../../api/axios'

const STATUS_LABELS = {
  pending_payment: 'Pending Payment',
  prepare: 'Preparing',
  sending: 'Shipping',
  received: 'Delivered',
}

function StatCard({ icon: Icon, label, value, color }) {
  return (
    <div className="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
      <div className={`p-3 rounded-xl ${color}`}>
        <Icon size={22} className="text-white" />
      </div>
      <div>
        <p className="text-sm text-gray-500">{label}</p>
        <p className="text-2xl font-bold text-gray-800">{value}</p>
      </div>
    </div>
  )
}

export default function AdminDashboard() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/admin/analytics.php')
      .then((r) => setStats(r.data.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  if (!stats) return <p className="text-gray-500">Failed to load analytics.</p>

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-800">Dashboard</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard icon={TrendingUp} label="Total Revenue" value={`${Number(stats.total_revenue).toLocaleString()} ₭`} color="bg-green-500" />
        <StatCard icon={ShoppingBag} label="Total Orders" value={stats.total_orders} color="bg-blue-500" />
        <StatCard icon={Users} label="Customers" value={stats.total_users} color="bg-purple-500" />
        <StatCard icon={Package} label="Products" value={stats.total_products} color="bg-orange-500" />
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        <div className="bg-white rounded-xl shadow-sm p-5">
          <h2 className="font-semibold text-gray-800 mb-4">Orders by Status</h2>
          <div className="space-y-2">
            {stats.orders_by_status?.map((s) => (
              <div key={s.status} className="flex justify-between items-center text-sm">
                <span className="text-gray-600">{STATUS_LABELS[s.status] || s.status}</span>
                <span className="font-semibold text-gray-800 bg-gray-100 px-2.5 py-0.5 rounded-full">{s.count}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm p-5">
          <h2 className="font-semibold text-gray-800 mb-4">Top Selling Products</h2>
          <div className="space-y-2">
            {stats.popular_products?.map((p, i) => (
              <div key={p.id} className="flex items-center gap-3 text-sm">
                <span className="w-5 h-5 rounded-full bg-green-100 text-green-700 text-xs flex items-center justify-center font-bold shrink-0">{i + 1}</span>
                <span className="flex-1 text-gray-700 truncate">{p.name}</span>
                <span className="text-gray-500 shrink-0">{p.total_sold} sold</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {stats.low_stock?.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm p-5">
          <div className="flex items-center gap-2 mb-4">
            <AlertTriangle size={18} className="text-yellow-500" />
            <h2 className="font-semibold text-gray-800">Low Stock Alert</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-gray-500 border-b">
                  <th className="pb-2 font-medium">Product</th>
                  <th className="pb-2 font-medium text-right">Stock</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {stats.low_stock.map((p) => (
                  <tr key={p.id}>
                    <td className="py-2 text-gray-700">{p.name}</td>
                    <td className="py-2 text-right">
                      <span className="text-red-500 font-semibold">{p.stock}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm p-5">
        <h2 className="font-semibold text-gray-800 mb-4">Recent Orders</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-gray-500 border-b">
                <th className="pb-2 font-medium">Order #</th>
                <th className="pb-2 font-medium">Customer</th>
                <th className="pb-2 font-medium">Status</th>
                <th className="pb-2 font-medium text-right">Total</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {stats.recent_orders?.map((o) => (
                <tr key={o.id}>
                  <td className="py-2 text-gray-700">#{o.id}</td>
                  <td className="py-2 text-gray-700">{o.customer_name}</td>
                  <td className="py-2">
                    <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                      {STATUS_LABELS[o.status] || o.status}
                    </span>
                  </td>
                  <td className="py-2 text-right font-medium text-green-700">{Number(o.total_price).toLocaleString()} ₭</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
