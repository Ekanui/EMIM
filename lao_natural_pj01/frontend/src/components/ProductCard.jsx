import { Link } from 'react-router-dom'
import { ShoppingCart } from 'lucide-react'
import useCartStore from '../store/cartStore'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://mimphaphon.kesug.com/backend'

export default function ProductCard({ product }) {
  const addItem = useCartStore((s) => s.addItem)

  const hasMultipleSizes = product.sizes && product.sizes.length > 1
  const displayPrice = product.sizes?.length
    ? Math.min(...product.sizes.map((s) => s.price))
    : product.price

  const handleAddToCart = (e) => {
    e.preventDefault()
    if (hasMultipleSizes) return
    const size = product.sizes?.[0]?.size ?? null
    addItem(product, size, 1)
  }

  return (
    <Link to={`/products/${product.id}`} className="group bg-white rounded-xl shadow hover:shadow-lg transition-shadow overflow-hidden flex flex-col">
      <div className="aspect-square overflow-hidden bg-gray-100">
        <img
          src={product.image_url ? `${BASE_URL}/uploads/${product.image_url}` : '/vite.svg'}
          alt={product.name}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => { e.target.src = '/vite.svg' }}
        />
      </div>
      <div className="p-3 flex flex-col flex-1">
        <p className="text-xs text-green-600 font-medium uppercase tracking-wide mb-1">{product.category}</p>
        <h3 className="font-semibold text-gray-800 text-sm line-clamp-2 flex-1">{product.name}</h3>
        <div className="mt-2 flex items-center justify-between">
          <span className="text-green-700 font-bold text-sm">
            {hasMultipleSizes ? 'From ' : ''}{Number(displayPrice).toLocaleString()} ₭
          </span>
          <button
            onClick={handleAddToCart}
            className="bg-green-600 hover:bg-green-700 text-white p-1.5 rounded-lg transition-colors"
            title={hasMultipleSizes ? 'Select options' : 'Add to cart'}
          >
            <ShoppingCart size={16} />
          </button>
        </div>
        {product.stock === 0 && (
          <span className="mt-1 text-xs text-red-500 font-medium">Out of stock</span>
        )}
      </div>
    </Link>
  )
}
