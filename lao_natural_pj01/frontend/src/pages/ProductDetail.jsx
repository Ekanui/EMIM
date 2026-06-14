import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ShoppingCart, ArrowLeft } from 'lucide-react'
import api from '../api/axios'
import useCartStore from '../store/cartStore'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://mimphaphon.kesug.com/backend'

export default function ProductDetail() {
  const { id } = useParams()
  const [product, setProduct] = useState(null)
  const [loading, setLoading] = useState(true)
  const [selectedSize, setSelectedSize] = useState(null)
  const [qty, setQty] = useState(1)
  const [added, setAdded] = useState(false)
  const addItem = useCartStore((s) => s.addItem)

  useEffect(() => {
    api.get(`/products/read_single.php?id=${id}`)
      .then((r) => {
        const p = r.data.data
        setProduct(p)
        if (p?.sizes?.length) setSelectedSize(p.sizes[0].size)
      })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [id])

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  if (!product) {
    return <div className="text-center py-20 text-gray-500">Product not found.</div>
  }

  const currentSizeData = product.sizes?.find((s) => s.size === selectedSize)
  const displayPrice = currentSizeData?.price ?? product.price
  const displayStock = currentSizeData?.stock ?? product.stock

  const handleAddToCart = () => {
    addItem(product, selectedSize, qty)
    setAdded(true)
    setTimeout(() => setAdded(false), 2000)
  }

  return (
    <div className="max-w-5xl mx-auto px-4 py-6">
      <Link to="/products" className="flex items-center gap-1 text-green-600 text-sm mb-5 hover:underline">
        <ArrowLeft size={16} /> Back to products
      </Link>

      <div className="grid md:grid-cols-2 gap-8">
        <div className="aspect-square bg-gray-100 rounded-2xl overflow-hidden">
          <img
            src={product.image_url ? `${BASE_URL}/uploads/${product.image_url}` : '/vite.svg'}
            alt={product.name}
            className="w-full h-full object-cover"
            onError={(e) => { e.target.src = '/vite.svg' }}
          />
        </div>

        <div className="flex flex-col gap-4">
          <div>
            <span className="text-xs text-green-600 uppercase font-semibold tracking-wider">{product.category}</span>
            <h1 className="text-2xl font-bold text-gray-900 mt-1">{product.name}</h1>
          </div>

          <p className="text-3xl font-bold text-green-700">{Number(displayPrice).toLocaleString()} ₭</p>

          {product.description && (
            <p className="text-gray-600 text-sm leading-relaxed">{product.description}</p>
          )}

          {product.ingredients && (
            <div className="bg-green-50 rounded-lg p-3">
              <h3 className="text-sm font-semibold text-gray-700 mb-1">Ingredients</h3>
              <p className="text-xs text-gray-600">{product.ingredients}</p>
            </div>
          )}

          {product.sizes && product.sizes.length > 0 && (
            <div>
              <h3 className="text-sm font-semibold text-gray-700 mb-2">Size</h3>
              <div className="flex flex-wrap gap-2">
                {product.sizes.map((s) => (
                  <button
                    key={s.size}
                    onClick={() => setSelectedSize(s.size)}
                    className={`px-4 py-2 rounded-lg border text-sm font-medium transition-colors ${
                      selectedSize === s.size
                        ? 'bg-green-600 border-green-600 text-white'
                        : 'border-gray-300 text-gray-700 hover:border-green-400'
                    }`}
                  >
                    {s.size} — {Number(s.price).toLocaleString()} ₭
                  </button>
                ))}
              </div>
            </div>
          )}

          <div className="flex items-center gap-3">
            <span className="text-sm text-gray-600">Quantity:</span>
            <div className="flex items-center border border-gray-300 rounded-lg overflow-hidden">
              <button className="px-3 py-1.5 hover:bg-gray-100" onClick={() => setQty(Math.max(1, qty - 1))}>−</button>
              <span className="px-4 py-1.5 text-sm font-medium">{qty}</span>
              <button className="px-3 py-1.5 hover:bg-gray-100" onClick={() => setQty(Math.min(displayStock, qty + 1))}>+</button>
            </div>
            <span className="text-xs text-gray-400">{displayStock} in stock</span>
          </div>

          <button
            onClick={handleAddToCart}
            disabled={displayStock === 0}
            className="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition-colors"
          >
            <ShoppingCart size={20} />
            {displayStock === 0 ? 'Out of Stock' : added ? 'Added!' : 'Add to Cart'}
          </button>
        </div>
      </div>
    </div>
  )
}
