import { useEffect, useState, useRef } from 'react'
import { Plus, Trash2, Upload } from 'lucide-react'
import api from '../../api/axios'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost/lao_natural_pj01/backend'

export default function AdminBanners() {
  const [banners, setBanners] = useState([])
  const [loading, setLoading] = useState(true)
  const [uploading, setUploading] = useState(false)
  const fileRef = useRef()

  const fetchBanners = () => {
    setLoading(true)
    api.get('/banners/read.php')
      .then((r) => setBanners(r.data.data || []))
      .catch(console.error)
      .finally(() => setLoading(false))
  }

  useEffect(() => { fetchBanners() }, [])

  const handleUpload = async (e) => {
    const file = e.target.files[0]
    if (!file) return
    setUploading(true)
    try {
      const fd = new FormData()
      fd.append('banner', file)
      await api.post('/banners/upload.php', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      fetchBanners()
    } catch (err) {
      alert(err.response?.data?.message || 'Upload failed')
    } finally {
      setUploading(false)
      e.target.value = ''
    }
  }

  const handleDelete = async (id) => {
    if (!confirm('Delete this banner?')) return
    try {
      await api.delete(`/banners/delete.php?id=${id}`)
      setBanners((prev) => prev.filter((b) => b.id !== id))
    } catch (err) {
      alert(err.response?.data?.message || 'Delete failed')
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-800">Banners</h1>
        <button
          onClick={() => fileRef.current?.click()}
          disabled={uploading}
          className="flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
          {uploading ? <><div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" /> Uploading...</> : <><Upload size={16} /> Upload Banner</>}
        </button>
        <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handleUpload} />
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" /></div>
      ) : banners.length === 0 ? (
        <div className="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
          <Plus size={40} className="mx-auto mb-3 text-gray-300" />
          <p>No banners yet. Upload one to get started.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {banners.map((banner) => (
            <div key={banner.id} className="relative group bg-white rounded-xl shadow-sm overflow-hidden">
              <img
                src={`${BASE_URL}/uploads/${banner.image_url}`}
                alt="Banner"
                className="w-full aspect-[16/5] object-cover"
                onError={(e) => { e.target.src = '/vite.svg' }}
              />
              <button
                onClick={() => handleDelete(banner.id)}
                className="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Trash2 size={16} />
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
