import { useEffect, useState, useRef } from 'react'
import { Upload } from 'lucide-react'
import api from '../../api/axios'

const BASE_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost/lao_natural_pj01/backend'

export default function AdminSettings() {
  const [settings, setSettings] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [form, setForm] = useState({ store_name: '', store_phone: '', store_email: '', store_address: '', bank_account: '', bank_name: '' })
  const [qrFile, setQrFile] = useState(null)
  const fileRef = useRef()

  useEffect(() => {
    api.get('/settings/read.php')
      .then((r) => {
        const d = r.data.data
        setSettings(d)
        setForm({
          store_name: d?.store_name || '',
          store_phone: d?.store_phone || '',
          store_email: d?.store_email || '',
          store_address: d?.store_address || '',
          bank_account: d?.bank_account || '',
          bank_name: d?.bank_name || '',
        })
      })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setMessage('')
    try {
      const payload = new FormData()
      Object.entries(form).forEach(([k, v]) => payload.append(k, v))
      if (qrFile) payload.append('qr_code', qrFile)
      await api.post('/settings/update.php', payload, { headers: { 'Content-Type': 'multipart/form-data' } })
      setMessage('Settings saved successfully')
    } catch (err) {
      setMessage(err.response?.data?.message || 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <div className="flex justify-center py-20"><div className="w-10 h-10 border-4 border-green-500 border-t-transparent rounded-full animate-spin" /></div>
  }

  return (
    <div className="space-y-4 max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-800">Settings</h1>

      <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm p-6 space-y-4">
        {message && (
          <div className={`text-sm rounded-lg p-3 ${message.includes('success') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'}`}>
            {message}
          </div>
        )}

        <div className="grid grid-cols-2 gap-4">
          {[
            { key: 'store_name', label: 'Store Name', span: 2 },
            { key: 'store_phone', label: 'Phone' },
            { key: 'store_email', label: 'Email', type: 'email' },
            { key: 'store_address', label: 'Address', span: 2 },
            { key: 'bank_name', label: 'Bank Name' },
            { key: 'bank_account', label: 'Bank Account' },
          ].map(({ key, label, type = 'text', span }) => (
            <div key={key} className={span === 2 ? 'col-span-2' : ''}>
              <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
              <input
                type={type}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                value={form[key]}
                onChange={(e) => setForm({ ...form, [key]: e.target.value })}
              />
            </div>
          ))}
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">Payment QR Code</label>
          <div
            onClick={() => fileRef.current?.click()}
            className="border-2 border-dashed border-gray-300 rounded-xl p-5 text-center cursor-pointer hover:border-green-400 transition-colors"
          >
            {qrFile ? (
              <img src={URL.createObjectURL(qrFile)} alt="QR" className="max-h-40 mx-auto rounded-lg object-contain" />
            ) : settings?.qr_code_url ? (
              <img src={`${BASE_URL}/uploads/${settings.qr_code_url}`} alt="QR" className="max-h-40 mx-auto rounded-lg object-contain" onError={(e) => { e.target.style.display = 'none' }} />
            ) : (
              <div className="flex flex-col items-center gap-1 text-gray-400 py-4">
                <Upload size={20} />
                <span className="text-sm">Click to upload QR code</span>
              </div>
            )}
          </div>
          <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={(e) => setQrFile(e.target.files[0] || null)} />
        </div>

        <button
          type="submit"
          disabled={saving}
          className="w-full bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold py-2.5 rounded-lg transition-colors"
        >
          {saving ? 'Saving...' : 'Save Settings'}
        </button>
      </form>
    </div>
  )
}
