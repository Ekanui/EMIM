import { Link } from 'react-router-dom'

export default function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center min-h-[70vh] gap-4 text-center px-4">
      <p className="text-8xl font-bold text-green-200">404</p>
      <h1 className="text-2xl font-bold text-gray-800">Page Not Found</h1>
      <p className="text-gray-500 max-w-sm">The page you're looking for doesn't exist or has been moved.</p>
      <Link to="/" className="bg-green-600 text-white px-6 py-2 rounded-full font-medium hover:bg-green-700 transition-colors">
        Go Home
      </Link>
    </div>
  )
}
