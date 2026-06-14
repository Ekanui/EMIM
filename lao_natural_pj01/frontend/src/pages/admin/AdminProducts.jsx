import { useEffect, useState, useRef } from 'react'
import { Plus, Pencil, Trash2, X, Upload } from 'lucide-react'
import api from '../../api/axios'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://mimphaphon.kesug.com/backend'

const EMPTY_FORM = { name: '', description: '', category: '', price: '', import_price: '', stock: '', ingredients: '', sizes: [] }

export default function AdminProducts() {
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(EMPTY_FORM)
  const [imageFile, setImageFile] = useState(null)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const fileRef = useRef()

  const fetchProducts = () => {
    setLoading(true)
    api.get('/products/read.php')
      .then((r) => setProducts(r.data.data || []))
      .catch(console.error)
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    fetchProducts()
    api.get('/categories/read.php').then((r) => setCategories(r.data.data || [])).catch(console.error)
  }, [])

  const openCreate = () => {
    setEditing(null)
    setForm(EMPTY_FORM)
    setImageFile(null)
    setError('')
    setShowModal(true)
  }

  const openEdit = (p) => {
    setEditing(p)
    setForm({
      name: p.name, description: p.description || '', category: p.category || '',
      price: p.price, import_price: p.import_price || '', stock: p.stock,
      ingredients: p.ingredients || '', sizes: p.sizes || [],
    })
    setImageFile(null)
    setError('')
    setShowModal(true)
  }

  const handleDelete = async (id) => {
    if (!confirm('Delete this product?')) return
    try {
      await api.delete(`/products/delete.php?id=${id}`)
      fetchProducts()
    } catch (err) {
      alert(err.response?.data?.message || 'Delete failed')
    }
  }

  const addSize = () => setForm((f) => ({ ...f, sizes: [...f.sizes, { size: '', price: '', import_price: '', stock: '' }] }))
  const removeSize = (i) => setForm((f) => ({ ...f, sizes: f.sizes.filter((_, idx) => idx !== i) }))
  const updateSize = (i, key, val) =>
    setForm((f) => ({ ...f, sizes: f.sizes.map((s, idx) => idx === i ? { ...s, [key]: val } : s) }))

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError('')
    try {
      let image_url = editing?.image_url || ''
      if (imageFile) {
        const fd = new FormData()
        fd.append('image', imageFile)
        const r = await api.post('/products/upload_image.php', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        image_url = r.data.filename
      }

      const payload = { ...form, image_url }
      if (editing) {
        await api.put(`/products/update.php?id=${editing.id}`, payload)
      } else {
        await api.post('/products/create.php', payload)
      }
      setShowModal(false)
      fetchProducts()
    } catch (err) {
      setError(err.response?.data?.message || 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-800">Products</h1>
        <button onClick={openCreate} className="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          <Plus size={16} /> Add Product
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" /></div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="text-left px-4 py-3 font-medium text-gray-600">Product</th>
                <th className="text-left px-4 py-3 font-medium text-gray-600">Category</th>
                <th className="text-right px-4 py-3 font-medium text-gray-600">Price</th>
                <th className="text-right px-4 py-3 font-medium text-gray-600">Stock</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {products.map((p) => (
                <tr key={p.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <img
                        src={p.image_url ? `${BASE_URL}/uploads/${p.image_url}` : '/vite.svg'}
                        alt={p.name}
                        className="w-10 h-10 object-cover rounded-lg bg-gray-100"
                        onError={(e) => { e.target.src = '/vite.svg' }}
                      />
                      <span className="font-medium text-gray-800">{p.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-600">{p.category}</td>
                  <td className="px-4 py-3 text-right text-green-700 font-medium">{Number(p.price).toLocaleString()} ₭</td>
                  <td className="px-4 py-3 text-right">
                    <span className={p.stock < 10 ? 'text-red-500 font-semibold' : 'text-gray-700'}>{p.stock}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2 justify-end">
                      <button onClick={() => openEdit(p)} className="text-blue-500 hover:text-blue-700"><Pencil size={16} /></button>
                      <button onClick={() => handleDelete(p.id)} className="text-red-400 hover:text-red-600"><Trash2 size={16} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-5 border-b">
              <h2 className="font-bold text-gray-800">{editing ? 'Edit Product' : 'Add Product'}</h2>
              <button onClick={() => setShowModal(false)}><X size={20} /></button>
            </div>
            <form onSubmit={handleSubmit} className="p-5 space-y-4">
              {error && <div className="bg-red-50 text-red-600 text-sm rounded-lg p-3">{error}</div>}

              <div className="grid grid-cols-2 gap-3">
                {[
                  { key: 'name', label: 'Name', span: 2 },
                  { key: 'price', label: 'Price (₭)', type: 'number' },
                  { key: 'import_price', label: 'Import Price (₭)', type: 'number' },
                  { key: 'stock', label: 'Stock', type: 'number' },
                ].map(({ key, label, type = 'text', span }) => (
                  <div key={key} className={span === 2 ? 'col-span-2' : ''}>
                    <label className="block text-xs font-medium text-gray-700 mb-1">{label}</label>
                    <input
                      type={type}
                      required={['name', 'price', 'stock'].includes(key)}
                      className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                      value={form[key]}
                      onChange={(e) => setForm({ ...form, [key]: e.target.value })}
                    />
                  </div>
                ))}
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Category</label>
                <select
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                  value={form.category}
                  onChange={(e) => setForm({ ...form, category: e.target.value })}
                >
                  <option value="">No category</option>
                  {categories.map((c) => <option key={c.id} value={c.name}>{c.name}</option>)}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  rows={2}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Ingredients</label>
                <textarea
                  rows={2}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                  value={form.ingredients}
                  onChange={(e) => setForm({ ...form, ingredients: e.target.value })}
                />
              </div>

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="text-xs font-medium text-gray-700">Sizes (optional)</label>
                  <button type="button" onClick={addSize} className="text-xs text-green-600 hover:underline">+ Add Size</button>
                </div>
                {form.sizes.map((s, i) => (
                  <div key={i} className="grid grid-cols-4 gap-2 mb-2 items-center">
                    <input placeholder="Size" className="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-green-500" value={s.size} onChange={(e) => updateSize(i, 'size', e.target.value)} />
                    <input placeholder="Price" type="number" className="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-green-500" value={s.price} onChange={(e) => updateSize(i, 'price', e.target.value)} />
                    <input placeholder="Stock" type="number" className="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-green-500" value={s.stock} onChange={(e) => updateSize(i, 'stock', e.target.value)} />
                    <button type="button" onClick={() => removeSize(i)} className="text-red-400 hover:text-red-600 flex justify-center"><X size={16} /></button>
                  </div>
                ))}
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-700 mb-1">Product Image</label>
                <div
                  onClick={() => fileRef.current?.click()}
                  className="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center cursor-pointer hover:border-green-400 transition-colors"
                >
                  {imageFile ? (
                    <img src={URL.createObjectURL(imageFile)} alt="" className="max-h-28 mx-auto rounded-lg object-contain" />
                  ) : editing?.image_url ? (
                    <img src={`${BASE_URL}/uploads/${editing.image_url}`} alt="" className="max-h-28 mx-auto rounded-lg object-contain" />
                  ) : (
                    <div className="flex flex-col items-center gap-1 text-gray-400 py-4">
                      <Upload size={20} />
                      <span className="text-xs">Click to upload</span>
                    </div>
                  )}
                </div>
                <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={(e) => setImageFile(e.target.files[0] || null)} />
              </div>

              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)} className="flex-1 border border-gray-300 text-gray-600 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</button>
                <button type="submit" disabled={saving} className="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                  {saving ? 'Saving...' : editing ? 'Update' : 'Create'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
