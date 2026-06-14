import { useEffect, useState } from 'react'
import { ChevronDown, ChevronUp } from 'lucide-react'
import api from '../../api/axios'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost/lao_natural_pj01/backend'

const STATUSES = ['pending_payment', 'prepare', 'sending', 'received']
const STATUS_LABELS = {
  pending_payment: { label: 'Pending Payment', color: 'bg-yellow-100 text-yellow-800' },
  prepare: { label: 'Preparing', color: 'bg-blue-100 text-blue-800' },
  sending: { label: 'Shipping', color: 'bg-purple-100 text-purple-800' },
  received: { label: 'Delivered', color: 'bg-green-100 text-green-800' },
}

export default function AdminOrders() {
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)
  const [expanded, setExpanded] = useState(null)
  const [filterStatus, setFilterStatus] = useState('')

  const fetchOrders = () => {
    setLoading(true)
    api.get('/orders/read.php')
      .then((r) => setOrders(r.data.data || []))
      .catch(console.error)
      .finally(() => setLoading(false))
  }

  useEffect(() => { fetchOrders() }, [])

  const handleStatusChange = async (orderId, newStatus) => {
    try {
      await api.put('/orders/update_status.php', { order_id: orderId, status: newStatus })
      setOrders((prev) => prev.map((o) => o.id === orderId ? { ...o, status: newStatus } : o))
    } catch (err) {
      alert(err.response?.data?.message || 'Update failed')
    }
  }

  const filtered = filterStatus ? orders.filter((o) => o.status === filterStatus) : orders

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between flex-wrap gap-3">
        <h1 className="text-2xl font-bold text-gray-800">Orders</h1>
        <div className="flex gap-2 flex-wrap">
          <button onClick={() => setFilterStatus('')} className={`px-3 py-1.5 rounded-full text-xs font-medium transition-colors ${!filterStatus ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>All</button>
          {STATUSES.map((s) => (
            <button key={s} onClick={() => setFilterStatus(s)} className={`px-3 py-1.5 rounded-full text-xs font-medium transition-colors ${filterStatus === s ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}>
              {STATUS_LABELS[s].label}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" /></div>
      ) : (
        <div className="space-y-3">
          {filtered.length === 0 && <p className="text-center text-gray-400 py-10">No orders found.</p>}
          {filtered.map((order) => {
            const s = STATUS_LABELS[order.status] || { label: order.status, color: 'bg-gray-100 text-gray-600' }
            const isOpen = expanded === order.id
            return (
              <div key={order.id} className="bg-white rounded-xl shadow-sm overflow-hidden">
                <div
                  className="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-gray-50"
                  onClick={() => setExpanded(isOpen ? null : order.id)}
                >
                  <div className="flex items-center gap-4">
                    <div>
                      <p className="font-semibold text-gray-800 text-sm">Order #{order.id}</p>
                      <p className="text-xs text-gray-400">{order.customer_name} · {new Date(order.created_at).toLocaleDateString()}</p>
                    </div>
                    <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${s.color}`}>{s.label}</span>
                  </div>
                  <div className="flex items-center gap-4">
                    <span className="font-bold text-green-700 text-sm">{Number(order.total_price).toLocaleString()} ₭</span>
                    {isOpen ? <ChevronUp size={16} className="text-gray-400" /> : <ChevronDown size={16} className="text-gray-400" />}
                  </div>
                </div>

                {isOpen && (
                  <div className="border-t px-5 py-4 space-y-4">
                    <div className="grid sm:grid-cols-2 gap-4 text-sm text-gray-600">
                      <div>
                        <p className="font-medium text-gray-700 mb-1">Shipping</p>
                        <p>{order.shipping_name} · {order.shipping_phone}</p>
                        <p>{order.shipping_address}</p>
                        <p>{order.province}</p>
                      </div>
                      {order.payment_screenshot && (
                        <div>
                          <p className="font-medium text-gray-700 mb-1">Payment Proof</p>
                          <img
                            src={`${BASE_URL}/uploads/${order.payment_screenshot}`}
                            alt="Payment"
                            className="w-32 rounded-lg border object-cover"
                            onError={(e) => { e.target.style.display = 'none' }}
                          />
                        </div>
                      )}
                    </div>

                    <div className="divide-y divide-gray-50">
                      {order.items?.map((item) => (
                        <div key={item.id} className="flex items-center gap-3 py-2 text-sm">
                          <img src={item.image_url ? `${BASE_URL}/uploads/${item.image_url}` : '/vite.svg'} alt={item.name} className="w-10 h-10 rounded-lg object-cover bg-gray-100" onError={(e) => { e.target.src = '/vite.svg' }} />
                          <span className="flex-1 text-gray-700">{item.name}{item.size ? ` (${item.size})` : ''}</span>
                          <span className="text-gray-500">×{item.quantity}</span>
                          <span className="font-medium text-gray-800">{Number(item.price * item.quantity).toLocaleString()} ₭</span>
                        </div>
                      ))}
                    </div>

                    <div className="flex items-center gap-3">
                      <label className="text-sm font-medium text-gray-700">Update Status:</label>
                      <select
                        value={order.status}
                        onChange={(e) => handleStatusChange(order.id, e.target.value)}
                        className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                      >
                        {STATUSES.map((s) => <option key={s} value={s}>{STATUS_LABELS[s].label}</option>)}
                      </select>
                    </div>
                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
