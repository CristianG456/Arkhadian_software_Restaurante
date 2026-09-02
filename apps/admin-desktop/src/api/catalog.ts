import { apiRequest } from './client'
import type { ApiSuccess } from './types'

export type ProductChannel = 'ADMIN_POS' | 'WAITER' | 'CUSTOMER_QR' | 'PUBLIC_WEB'
export interface ProductVariant { id: string; name: string | null; price: string; isDefault: boolean; isActive: boolean; isAvailable: boolean; sortOrder: number }
export interface ProductImage { id: string; mimeType: string; fileSize: number; width: number; height: number; isPrimary: boolean; url: string }
export interface ModifierOption { id: string; name: string; priceDelta: string; isActive: boolean; sortOrder: number }
export interface ModifierGroup { id: string; name: string; isActive: boolean; assignmentsCount: number; options: ModifierOption[] }
export interface ModifierAssignment { id: string; modifierGroupId: string; minSelect: number; maxSelect: number; isActive: boolean; group: ModifierGroup }
export interface Product { id: string; categoryId: string; name: string; description: string | null; isActive: boolean; isAvailable: boolean; allowNotes: boolean; requiresProduction: boolean; productionCenterId: string | null; productionCenter?: { id: string; name: string } | null; sortOrder: number; variantsCount: number; variants: ProductVariant[]; basePrice: string | null; primaryImage: ProductImage | null; channels?: Record<ProductChannel, boolean>; images?: ProductImage[]; modifierAssignments?: ModifierAssignment[] }
export interface ProductCategory { id: string; name: string; description: string | null; sortOrder: number; isActive: boolean; productsCount: number; products: Product[] }

export const fetchCategories = (signal?: AbortSignal) => apiRequest<ApiSuccess<{ categories: ProductCategory[] }>>('/api/catalog/categories', { signal })
export const createCategory = (name: string) => apiRequest<ApiSuccess<{ category: ProductCategory }>>('/api/catalog/categories', { method: 'POST', body: { name } })
export const updateCategory = (id: string, body: Partial<Pick<ProductCategory, 'name' | 'description' | 'isActive'>>) => apiRequest(`/api/catalog/categories/${id}`, { method: 'PUT', body })
export const deleteCategory = (id: string) => apiRequest(`/api/catalog/categories/${id}`, { method: 'DELETE' })
export const createProduct = (body: { categoryId: string; name: string; price: string; description?: string }) => apiRequest<ApiSuccess<{ product: Product }>>('/api/catalog/products', { method: 'POST', body })
export const fetchProduct = (id: string) => apiRequest<ApiSuccess<{ product: Product }>>(`/api/catalog/products/${id}`)
export const updateProduct = (id: string, body: Record<string, unknown>) => apiRequest<ApiSuccess<{ product: Product }>>(`/api/catalog/products/${id}`, { method: 'PUT', body })
export const setAvailability = (id: string, isAvailable: boolean) => apiRequest(`/api/catalog/products/${id}/availability`, { method: 'PUT', body: { isAvailable } })
export const duplicateProduct = (id: string) => apiRequest(`/api/catalog/products/${id}/duplicate`, { method: 'POST' })
export const deleteProduct = (id: string) => apiRequest(`/api/catalog/products/${id}`, { method: 'DELETE' })
export const createVariant = (productId: string, body: { name: string; price: string; isDefault?: boolean }) => apiRequest(`/api/catalog/products/${productId}/variants`, { method: 'POST', body })
export const updateVariant = (productId: string, id: string, body: Record<string, unknown>) => apiRequest(`/api/catalog/products/${productId}/variants/${id}`, { method: 'PUT', body })
export const deleteVariant = (productId: string, id: string) => apiRequest(`/api/catalog/products/${productId}/variants/${id}`, { method: 'DELETE' })
export const updateChannels = (id: string, channels: Record<ProductChannel, boolean>) => apiRequest(`/api/catalog/products/${id}/channels`, { method: 'PUT', body: { channels } })
export const fetchModifierGroups = () => apiRequest<ApiSuccess<{ modifierGroups: ModifierGroup[] }>>('/api/catalog/modifier-groups')
export const createModifierGroup = (name: string) => apiRequest('/api/catalog/modifier-groups', { method: 'POST', body: { name } })
export const updateModifierGroup = (id: string, body: { name?: string; isActive?: boolean }) => apiRequest(`/api/catalog/modifier-groups/${id}`, { method: 'PUT', body })
export const deleteModifierGroup = (id: string) => apiRequest(`/api/catalog/modifier-groups/${id}`, { method: 'DELETE' })
export const createModifierOption = (groupId: string, body: { name: string; priceDelta: string }) => apiRequest(`/api/catalog/modifier-groups/${groupId}/options`, { method: 'POST', body })
export const updateModifierOption = (groupId: string, id: string, body: { name?: string; priceDelta?: string; isActive?: boolean }) => apiRequest(`/api/catalog/modifier-groups/${groupId}/options/${id}`, { method: 'PUT', body })
export const deleteModifierOption = (groupId: string, id: string) => apiRequest(`/api/catalog/modifier-groups/${groupId}/options/${id}`, { method: 'DELETE' })
export const assignModifier = (productId: string, body: { modifierGroupId: string; minSelect: number; maxSelect: number }) => apiRequest(`/api/catalog/products/${productId}/modifier-groups`, { method: 'POST', body })
export const unassignModifier = (productId: string, id: string) => apiRequest(`/api/catalog/products/${productId}/modifier-groups/${id}`, { method: 'DELETE' })
export const uploadImage = (productId: string, file: File) => { const body = new FormData(); body.append('image', file); return apiRequest(`/api/catalog/products/${productId}/images`, { method: 'POST', body, timeoutMs: 30000 }) }
export const deleteImage = (productId: string, id: string) => apiRequest(`/api/catalog/products/${productId}/images/${id}`, { method: 'DELETE' })
