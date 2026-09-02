import { useCallback, useEffect, useState } from 'react'
import * as api from '../api/catalog'
import { fetchCenters, type ProductionCenter } from '../api/production'
import { ApiClientError } from '../api/ApiClientError'

export function catalogError(error: unknown) { return error instanceof ApiClientError ? error.userMessage : 'No pudimos completar la operación.' }

export function useCatalog() {
  const [categories, setCategories] = useState<api.ProductCategory[]>([])
  const [modifierGroups, setModifierGroups] = useState<api.ModifierGroup[]>([])
  const [productionCenters, setProductionCenters] = useState<ProductionCenter[]>([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async (signal?: AbortSignal) => {
    try {
      const [catalog, modifiers, production] = await Promise.all([api.fetchCategories(signal), api.fetchModifierGroups(), fetchCenters()])
      setCategories(catalog.data.categories); setModifierGroups(modifiers.data.modifierGroups); setProductionCenters(production.data.centers); setError(null)
    } catch (cause) { if (!signal?.aborted) setError(catalogError(cause)) }
    finally { if (!signal?.aborted) setLoading(false) }
  }, [])

  useEffect(() => {
    const controller = new AbortController()
    const startId = window.setTimeout(() => void load(controller.signal), 0)
    return () => { window.clearTimeout(startId); controller.abort() }
  }, [load])
  async function mutate<T>(operation: () => Promise<T>) { setSaving(true); try { const result = await operation(); await load(); return result } finally { setSaving(false) } }

  return { categories, modifierGroups, productionCenters, loading, saving, error, clearError: () => setError(null), refresh: load,
    createCategory: (name: string) => mutate(() => api.createCategory(name)),
    updateCategory: (id: string, body: Parameters<typeof api.updateCategory>[1]) => mutate(() => api.updateCategory(id, body)),
    deleteCategory: (id: string) => mutate(() => api.deleteCategory(id)),
    createProduct: (body: Parameters<typeof api.createProduct>[0]) => mutate(() => api.createProduct(body)),
    updateProduct: (id: string, body: Record<string, unknown>) => mutate(() => api.updateProduct(id, body)),
    availability: (id: string, value: boolean) => mutate(() => api.setAvailability(id, value)),
    duplicate: (id: string) => mutate(() => api.duplicateProduct(id)), deleteProduct: (id: string) => mutate(() => api.deleteProduct(id)),
    fetchProduct: api.fetchProduct, createVariant: (id: string, body: Parameters<typeof api.createVariant>[1]) => mutate(() => api.createVariant(id, body)),
    updateVariant: (productId: string, id: string, body: Record<string, unknown>) => mutate(() => api.updateVariant(productId, id, body)),
    deleteVariant: (productId: string, id: string) => mutate(() => api.deleteVariant(productId, id)), updateChannels: (id: string, channels: api.Product['channels'] extends infer T ? NonNullable<T> : never) => mutate(() => api.updateChannels(id, channels)),
    createModifierGroup: (name: string) => mutate(() => api.createModifierGroup(name)),
    updateModifierGroup: (id: string, body: Parameters<typeof api.updateModifierGroup>[1]) => mutate(() => api.updateModifierGroup(id, body)),
    deleteModifierGroup: (id: string) => mutate(() => api.deleteModifierGroup(id)),
    createModifierOption: (id: string, body: Parameters<typeof api.createModifierOption>[1]) => mutate(() => api.createModifierOption(id, body)),
    updateModifierOption: (groupId: string, id: string, body: Parameters<typeof api.updateModifierOption>[2]) => mutate(() => api.updateModifierOption(groupId, id, body)),
    deleteModifierOption: (groupId: string, id: string) => mutate(() => api.deleteModifierOption(groupId, id)),
    assignModifier: (id: string, body: Parameters<typeof api.assignModifier>[1]) => mutate(() => api.assignModifier(id, body)), unassignModifier: (id: string, assignment: string) => mutate(() => api.unassignModifier(id, assignment)),
    uploadImage: (id: string, file: File) => mutate(() => api.uploadImage(id, file)), deleteImage: (id: string, image: string) => mutate(() => api.deleteImage(id, image)),
  }
}
