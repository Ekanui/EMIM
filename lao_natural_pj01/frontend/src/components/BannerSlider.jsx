import { useState, useEffect } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://emim-production.up.railway.app'

export default function BannerSlider({ banners }) {
  const [current, setCurrent] = useState(0)

  useEffect(() => {
    if (banners.length <= 1) return
    const timer = setInterval(() => setCurrent((c) => (c + 1) % banners.length), 4000)
    return () => clearInterval(timer)
  }, [banners.length])

  if (!banners.length) return null

  const prev = () => setCurrent((c) => (c - 1 + banners.length) % banners.length)
  const next = () => setCurrent((c) => (c + 1) % banners.length)

  return (
    <div className="relative w-full overflow-hidden rounded-xl shadow-md aspect-[16/5] bg-gray-200">
      {banners.map((banner, i) => (
        <img
          key={banner.id}
          src={`${BASE_URL}/uploads/${banner.image_url}`}
          alt={`Banner ${i + 1}`}
          className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ${i === current ? 'opacity-100' : 'opacity-0'}`}
          onError={(e) => { e.target.src = '/vite.svg' }}
        />
      ))}

      {banners.length > 1 && (
        <>
          <button onClick={prev} className="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full p-1.5 transition-colors">
            <ChevronLeft size={20} />
          </button>
          <button onClick={next} className="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full p-1.5 transition-colors">
            <ChevronRight size={20} />
          </button>
          <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
            {banners.map((_, i) => (
              <button
                key={i}
                onClick={() => setCurrent(i)}
                className={`w-2 h-2 rounded-full transition-colors ${i === current ? 'bg-white' : 'bg-white/50'}`}
              />
            ))}
          </div>
        </>
      )}
    </div>
  )
}
