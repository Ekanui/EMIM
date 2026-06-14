import { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import useCartStore from '../store/cartStore'

const PROVINCES = [
  'Vientiane Capital', 'Phongsali', 'Luang Namtha', 'Oudomxay', 'Bokeo',
  'Luang Prabang', 'Huaphanh', 'Xayabury', 'Xieng Khouang', 'Vientiane Province',
  'Bolikhamsai', 'Khammuane', 'Savannakhet', 'Salavan', 'Xekong',
  'Champasak', 'Attapeu', 'Xaisomboun',
]

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://mimphaphon.kesug.com/backend'

export default function Checkout() {
  const { items, total, clearCart } = useCartStore()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    shipping_name: '', shipping_phone: '', shipping_address: '', province: '', shipping_cost: 0,
  })
  const [screenshot, setScreenshot] = useState(null)
  const [screenshotPreview, setScreenshotPreview] = useState(null)
  const [settings, setSettings] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const fileRef = useRef()

  useEffect(() => {
    api.get('/settings/read.php').then((r) => setSettings(r.data.data)).catch(console.error)
  }, [])

  useEffect(() => {
    if (items.length === 0) navigate('/cart')
  }, [items, navigate])

  const subtotal = total()
  const vat = subtotal * 0.1
  const grandTotal = subtotal + vat + Number(form.shipping_cost)

  const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    setScreenshot(file)
    setScreenshotPreview(URL.createObjectURL(file))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!screenshot) { setError('Please upload payment screenshot'); return }
    setError('')
    setLoading(true)
    try {
      const fd = new FormData()
      fd.append('screenshot', screenshot)
      const uploadRes = await api.post('/orders/upload_screenshot.php', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      const screenshotPath = uploadRes.data.filename

      await api.post('/orders/create.php', {
        items: items.map((i) => ({ id: i.id, quantity: i.quantity, price: i.price, selectedSize: i.selectedSize })),
        ...form,
        payment_screenshot: screenshotPath,
      })
      clearCart()
      navigate('/orders')
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to place order')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-5xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">Checkout</h1>

      <div className="grid md:grid-cols-3 gap-6">
        <form onSubmit={handleSubmit} className="md:col-span-2 space-y-4">
          <div className="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <h2 className="font-semibold text-gray-800">Shipping Information</h2>
            {[
              { key: 'shipping_name', label: 'Recipient Name', type: 'text' },
              { key: 'shipping_phone', label: 'Phone Number', type: 'tel' },
              { key: 'shipping_address', label: 'Address', type: 'text' },
            ].map(({ key, label, type }) => (
              <div key={key}>
                <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <input
                  type={type}
                  required
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                  value={form[key]}
                  onChange={(e) => setForm({ ...form, [key]: e.target.value })}
                />
              </div>
            ))}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Province</label>
              <select
                required
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                value={form.province}
                onChange={(e) => setForm({ ...form, province: e.target.value })}
              >
                <option value="">Select province...</option>
                {PROVINCES.map((p) => <option key={p} value={p}>{p}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Shipping Cost (₭)</label>
              <input
                type="number"
                min="0"
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                value={form.shipping_cost}
                onChange={(e) => setForm({ ...form, shipping_cost: e.target.value })}
              />
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm p-5">
            <h2 className="font-semibold text-gray-800 mb-3">Payment</h2>
            {settings?.qr_code_url && (
              <div className="mb-3 text-center">
                <p className="text-sm text-gray-600 mb-2">Scan QR code to pay:</p>
                <img src={`${BASE_URL}/uploads/${settings.qr_code_url}`} alt="QR Code" className="w-40 mx-auto rounded-lg border" onError={(e) => { e.target.style.display = 'none' }} />
              </div>
            )}
            <label className="block text-sm font-medium text-gray-700 mb-2">Upload Payment Screenshot *</label>
            <div
              onClick={() => fileRef.current?.click()}
              className="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-green-400 transition-colors"
            >
              {screenshotPreview ? (
                <img src={screenshotPreview} alt="Payment" className="max-h-40 mx-auto rounded-lg object-contain" />
              ) : (
                <p className="text-sm text-gray-400">Click to upload screenshot</p>
              )}
            </div>
            <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handleFileChange} />
          </div>

          {error && <div className="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg p-3">{error}</div>}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors"
          >
            {loading ? 'Placing Order...' : 'Place Order'}
          </button>
        </form>

        <div className="bg-white rounded-xl shadow-sm p-5 h-fit">
          <h2 className="font-bold text-gray-800 mb-4">Order Summary</h2>
          <div className="space-y-2 text-sm text-gray-600 mb-4">
            {items.map((i) => (
              <div key={i.key} className="flex justify-between">
                <span className="truncate pr-2">{i.name} ×{i.quantity}</span>
                <span className="shrink-0">{Number(i.price * i.quantity).toLocaleString()} ₭</span>
              </div>
            ))}
          </div>
          <div className="border-t pt-3 space-y-1 text-sm text-gray-600">
            <div className="flex justify-between"><span>Subtotal</span><span>{Number(subtotal).toLocaleString()} ₭</span></div>
            <div className="flex justify-between"><span>VAT (10%)</span><span>{Number(vat).toLocaleString()} ₭</span></div>
            <div className="flex justify-between"><span>Shipping</span><span>{Number(form.shipping_cost).toLocaleString()} ₭</span></div>
          </div>
          <div className="border-t mt-3 pt-3 flex justify-between font-bold text-gray-800">
            <span>Total</span>
            <span>{Number(grandTotal).toLocaleString()} ₭</span>
          </div>
        </div>
      </div>
    </div>
  )
}
