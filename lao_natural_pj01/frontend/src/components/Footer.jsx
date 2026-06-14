import { Leaf } from 'lucide-react'

export default function Footer() {
  return (
    <footer className="bg-green-800 text-green-100 mt-auto">
      <div className="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <div className="flex items-center gap-2 font-bold text-white text-lg mb-2">
            <Leaf size={20} />
            Lao Natural Essentials
          </div>
          <p className="text-sm text-green-300">Natural products from Laos, delivered to your door.</p>
        </div>
        <div>
          <h4 className="font-semibold text-white mb-2">Quick Links</h4>
          <ul className="space-y-1 text-sm">
            <li><a href="/" className="hover:text-white transition-colors">Home</a></li>
            <li><a href="/products" className="hover:text-white transition-colors">Products</a></li>
            <li><a href="/orders" className="hover:text-white transition-colors">Orders</a></li>
          </ul>
        </div>
        <div>
          <h4 className="font-semibold text-white mb-2">Contact</h4>
          <p className="text-sm">Email: info@laonatural.com</p>
          <p className="text-sm">Phone: +856 20 xxxx xxxx</p>
        </div>
      </div>
      <div className="text-center text-xs text-green-400 py-3 border-t border-green-700">
        &copy; {new Date().getFullYear()} Lao Natural Essentials. All rights reserved.
      </div>
    </footer>
  )
}
