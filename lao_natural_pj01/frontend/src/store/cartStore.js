import { create } from 'zustand'

const useCartStore = create((set, get) => ({
  items: JSON.parse(localStorage.getItem('cart') || '[]'),

  addItem: (product, selectedSize, quantity = 1) => {
    const items = get().items
    const key = `${product.id}-${selectedSize}`
    const existing = items.find((i) => i.key === key)
    let updated
    if (existing) {
      updated = items.map((i) =>
        i.key === key ? { ...i, quantity: i.quantity + quantity } : i
      )
    } else {
      const price = selectedSize
        ? (product.sizes?.find((s) => s.size === selectedSize)?.price ?? product.price)
        : product.price
      updated = [...items, { key, id: product.id, name: product.name, image_url: product.image_url, price, selectedSize, quantity }]
    }
    localStorage.setItem('cart', JSON.stringify(updated))
    set({ items: updated })
  },

  removeItem: (key) => {
    const updated = get().items.filter((i) => i.key !== key)
    localStorage.setItem('cart', JSON.stringify(updated))
    set({ items: updated })
  },

  updateQuantity: (key, quantity) => {
    if (quantity < 1) return get().removeItem(key)
    const updated = get().items.map((i) => (i.key === key ? { ...i, quantity } : i))
    localStorage.setItem('cart', JSON.stringify(updated))
    set({ items: updated })
  },

  clearCart: () => {
    localStorage.removeItem('cart')
    set({ items: [] })
  },

  total: () => get().items.reduce((sum, i) => sum + i.price * i.quantity, 0),
  count: () => get().items.reduce((sum, i) => sum + i.quantity, 0),
}))

export default useCartStore
