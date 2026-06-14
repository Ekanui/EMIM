import { useEffect, useState, useRef } from 'react'
import { Camera } from 'lucide-react'
import api from '../api/axios'
import useAuthStore from '../store/authStore'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'https://emim-production.up.railway.app'

export default function Profile() {
  const { user, updateUser } = useAuthStore()
  const [profile, setProfile] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [form, setForm] = useState({ name: '', phone: '', address: '' })
  const fileRef = useRef()

  useEffect(() => {
    api.get('/user/profile.php')
      .then((r) => {
        const p = r.data.data
        setProfile(p)
        setForm({ name: p.name || '', phone: p.phone || '', address: p.address || '' })
      })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  const handleSave = async (e) => {
    e.preventDefault()
    setSaving(true)
    setMessage('')
    try {
      await api.put('/user/profile.php', form)
      updateUser({ ...user, name: form.name })
      setMessage('Profile updated successfully')
    } catch (err) {
      setMessage(err.response?.data?.message || 'Update failed')
    } finally {
      setSaving(false)
    }
  }

  const handlePhotoUpload = async (e) => {
    const file = e.target.files[0]
    if (!file) return
    const fd = new FormData()
    fd.append('profile_picture', file)
    try {
      const r = await api.post('/user/profile.php', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      setProfile((p) => ({ ...p, profile_picture: r.data.filename }))
    } catch (err) {
      console.error(err)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-[60vh]">
        <div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" />
      </div>
    )
  }

  return (
    <div className="max-w-lg mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">My Profile</h1>

      <div className="bg-white rounded-2xl shadow-sm p-6">
        <div className="flex flex-col items-center mb-6">
          <div className="relative">
            <div className="w-24 h-24 rounded-full bg-gray-200 overflow-hidden">
              {profile?.profile_picture ? (
                <img src={`${BASE_URL}/uploads/${profile.profile_picture}`} alt="Profile" className="w-full h-full object-cover" />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-4xl text-gray-400 font-bold">
                  {profile?.name?.[0]?.toUpperCase() || '?'}
                </div>
              )}
            </div>
            <button
              onClick={() => fileRef.current?.click()}
              className="absolute bottom-0 right-0 bg-green-600 text-white rounded-full p-1.5 shadow hover:bg-green-700 transition-colors"
            >
              <Camera size={14} />
            </button>
            <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={handlePhotoUpload} />
          </div>
          <p className="mt-3 font-semibold text-gray-800">{profile?.name}</p>
          <p className="text-sm text-gray-500">{profile?.email}</p>
          <span className="mt-1 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full capitalize">{profile?.role}</span>
        </div>

        {message && (
          <div className={`text-sm rounded-lg p-3 mb-4 ${message.includes('success') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'}`}>
            {message}
          </div>
        )}

        <form onSubmit={handleSave} className="space-y-4">
          {[
            { key: 'name', label: 'Full Name', type: 'text' },
            { key: 'phone', label: 'Phone', type: 'tel' },
            { key: 'address', label: 'Address', type: 'text' },
          ].map(({ key, label, type }) => (
            <div key={key}>
              <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
              <input
                type={type}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                value={form[key]}
                onChange={(e) => setForm({ ...form, [key]: e.target.value })}
              />
            </div>
          ))}
          <button
            type="submit"
            disabled={saving}
            className="w-full bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold py-2 rounded-lg transition-colors"
          >
            {saving ? 'Saving...' : 'Save Changes'}
          </button>
        </form>
      </div>
    </div>
  )
}
