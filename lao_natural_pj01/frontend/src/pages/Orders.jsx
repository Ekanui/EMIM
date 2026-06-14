import { useEffect, useState } from 'react'
import api from '../api/axios'

const STATUS_LABELS = {
  pending_payment: { label: 'Pending Payment', color: 'bg-yellow-100 text-yellow-800' },
  prepare: { label: 'Preparing', color: 'bg-blue-100 text-blue-800' },
  sending: { label: 'Shipping', color: 'bg-purple-100 text-purple-800' },
  received: { label: 'Delivered', color: 'bg-green-100 text-green-800' },
}

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost/lao_natural_pj01/backend'

export default function Orders() {
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/orders/read.php')
      .then((r) => setOrders(r.data.data || []))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  if (orders.length === 0) {
    return <div className="text-center text-gray-500 py-20">No orders yet.</div>
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">My Orders</h1>
      <div className="space-y-4">
        {orders.map((order) => {
          const status = STATUS_LABELS[order.status] || { label: order.status, color: 'bg-gray-100 text-gray-700' }
          return (
            <div key={order.id} className="bg-white rounded-xl shadow-sm p-5">
              <div className="flex items-start justify-between mb-3">
                <div>
                  <p className="font-semibold text-gray-800">Order #{order.id}</p>
                  <p className="text-xs text-gray-400 mt-0.5">{new Date(order.created_at).toLocaleString()}</p>
                </div>
                <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${status.color}`}>{status.label}</span>
              </div>

              <div className="divide-y divide-gray-100">
                {order.items?.map((item) => (
                  <div key={item.id} className="flex items-center gap-3 py-2">
                    <img
                      src={item.image_url ? `${BASE_URL}/uploads/${item.image_url}` : '/vite.svg'}
                      alt={item.name}
                      className="w-12 h-12 object-cover rounded-lg bg-gray-100"
                      onError={(e) => { e.target.src = '/vite.svg' }}
                    />
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-800 truncate">{item.name}</p>
                      {item.size && <p className="text-xs text-gray-500">Size: {item.size}</p>}
                      <p className="text-xs text-gray-500">Qty: {item.quantity} × {Number(item.price).toLocaleString()} ₭</p>
                    </div>
                    <p className="text-sm font-semibold text-green-700 shrink-0">
                      {Number(item.price * item.quantity).toLocaleString()} ₭
                    </p>
                  </div>
                ))}
              </div>

              <div className="mt-3 pt-3 border-t flex justify-between items-center">
                <div className="text-xs text-gray-500">
                  <p>Ship to: {order.shipping_name}</p>
                  {order.express_tracking && <p>Tracking: {order.express_tracking}</p>}
                </div>
                <p className="font-bold text-gray-800">{Number(order.total_price).toLocaleString()} ₭</p>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
