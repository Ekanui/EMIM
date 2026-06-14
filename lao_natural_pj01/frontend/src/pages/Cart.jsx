import { Link } from 'react-router-dom'
import { Trash2, ShoppingBag } from 'lucide-react'
import useCartStore from '../store/cartStore'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://emim-production.up.railway.app'

export default function Cart() {
  const { items, removeItem, updateQuantity, total } = useCartStore()

  if (items.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-gray-500">
        <ShoppingBag size={56} className="text-gray-300" />
        <p className="text-lg font-medium">Your cart is empty</p>
        <Link to="/products" className="bg-green-600 text-white px-6 py-2 rounded-full font-medium hover:bg-green-700 transition-colors">
          Browse Products
        </Link>
      </div>
    )
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">Shopping Cart</h1>

      <div className="grid md:grid-cols-3 gap-6">
        <div className="md:col-span-2 space-y-3">
          {items.map((item) => (
            <div key={item.key} className="bg-white rounded-xl shadow-sm p-4 flex gap-4 items-center">
              <img
                src={item.image_url ? `${BASE_URL}/uploads/${item.image_url}` : '/vite.svg'}
                alt={item.name}
                className="w-20 h-20 object-cover rounded-lg bg-gray-100 shrink-0"
                onError={(e) => { e.target.src = '/vite.svg' }}
              />
              <div className="flex-1 min-w-0">
                <p className="font-medium text-gray-800 text-sm truncate">{item.name}</p>
                {item.selectedSize && <p className="text-xs text-gray-500">Size: {item.selectedSize}</p>}
                <p className="text-green-700 font-semibold text-sm mt-1">{Number(item.price).toLocaleString()} ₭</p>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                  <button className="px-2 py-1 hover:bg-gray-100 text-sm" onClick={() => updateQuantity(item.key, item.quantity - 1)}>−</button>
                  <span className="px-3 py-1 text-sm">{item.quantity}</span>
                  <button className="px-2 py-1 hover:bg-gray-100 text-sm" onClick={() => updateQuantity(item.key, item.quantity + 1)}>+</button>
                </div>
                <button onClick={() => removeItem(item.key)} className="text-red-400 hover:text-red-600 transition-colors">
                  <Trash2 size={18} />
                </button>
              </div>
            </div>
          ))}
        </div>

        <div className="bg-white rounded-xl shadow-sm p-5 h-fit">
          <h2 className="font-bold text-gray-800 mb-4">Order Summary</h2>
          <div className="space-y-2 text-sm text-gray-600 mb-4">
            <div className="flex justify-between">
              <span>Subtotal</span>
              <span>{Number(total()).toLocaleString()} ₭</span>
            </div>
            <div className="flex justify-between">
              <span>VAT (10%)</span>
              <span>{Number(total() * 0.1).toLocaleString()} ₭</span>
            </div>
          </div>
          <div className="border-t pt-3 flex justify-between font-bold text-gray-800 mb-5">
            <span>Total (before shipping)</span>
            <span>{Number(total() * 1.1).toLocaleString()} ₭</span>
          </div>
          <Link
            to="/checkout"
            className="block text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl transition-colors"
          >
            Proceed to Checkout
          </Link>
        </div>
      </div>
    </div>
  )
}
