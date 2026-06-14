import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../api/axios'
import BannerSlider from '../components/BannerSlider'
import ProductCard from '../components/ProductCard'

export default function Home() {
  const [banners, setBanners] = useState([])
  const [popular, setPopular] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    Promise.all([
      api.get('/banners/read.php'),
      api.get('/products/read_popular.php'),
      api.get('/categories/read.php'),
    ]).then(([b, p, c]) => {
      setBanners(b.data.data || [])
      setPopular(p.data.data || [])
      setCategories(c.data.data || [])
    }).catch(console.error).finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-6 space-y-10">
      {banners.length > 0 && <BannerSlider banners={banners} />}

      {categories.length > 0 && (
        <section>
          <h2 className="text-xl font-bold text-gray-800 mb-4">Shop by Category</h2>
          <div className="flex flex-wrap gap-3">
            {categories.map((cat) => (
              <Link
                key={cat.id}
                to={`/products?category=${encodeURIComponent(cat.name)}`}
                className="bg-green-50 border border-green-200 text-green-700 px-5 py-2 rounded-full text-sm font-medium hover:bg-green-100 transition-colors"
              >
                {cat.name}
              </Link>
            ))}
            <Link
              to="/products"
              className="bg-gray-100 border border-gray-200 text-gray-600 px-5 py-2 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors"
            >
              All Products
            </Link>
          </div>
        </section>
      )}

      {popular.length > 0 && (
        <section>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-bold text-gray-800">Popular Products</h2>
            <Link to="/products" className="text-green-600 text-sm font-medium hover:underline">View all</Link>
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {popular.map((p) => <ProductCard key={p.id} product={p} />)}
          </div>
        </section>
      )}
    </div>
  )
}
