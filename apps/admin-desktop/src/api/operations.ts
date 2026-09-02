import { apiRequest } from './client'
import type { ApiSuccess } from './types'
export interface StaffMember { id: string; name: string; type: 'WAITER'; isActive: boolean }
export interface OperationSessionSummary { id: string; waiter: StaffMember; openedAt: string; ordersCount: number; total: string }
export interface OperationTable { id: string; label: string; state: 'FREE' | 'OCCUPIED'; session: OperationSessionSummary | null }
export interface OperationArea { id: string; name: string; tables: OperationTable[] }
export interface OrderModifier { id: string; groupName: string; optionName: string; priceDelta: string }
export interface OrderItem { id: string; productName: string; variantName: string | null; quantity: number; unitPrice: string; unitTotal: string; lineTotal: string; notes: string | null; status: 'ACTIVE' | 'CANCELLED'; cancelReason: string | null; modifiers: OrderModifier[] }
export interface OrderPrintJob { id: string; jobType: string; status: 'PENDING' | 'PROCESSING' | 'PRINTED' | 'FAILED'; errorMessage: string | null; center: { id: string; name: string } | null }
export interface Order { id: string; orderNumber: number; source: string; status: 'CONFIRMED' | 'CANCELLED'; waiter: StaffMember; createdAt: string; cancelReason: string | null; items: OrderItem[]; printJobs: OrderPrintJob[] }
export interface TableSession { id: string; status: 'OPEN' | 'CLOSED'; openedAt: string; billRequestedAt: string | null; billRequestSource: string | null; table: { id: string; label: string; areaId: string; areaName: string }; waiter: StaffMember; total: string; orders: Order[]; waiterHistory: unknown[]; tableHistory: unknown[] }
export interface NewOrderItem { productId: string; variantId?: string; quantity: number; modifierOptionIds: string[]; notes?: string }
export const fetchOperations = (signal?: AbortSignal) => apiRequest<ApiSuccess<{ areas: OperationArea[] }>>('/api/operations/tables', { signal })
export const fetchStaff = (signal?: AbortSignal) => apiRequest<ApiSuccess<{ staff: StaffMember[] }>>('/api/staff', { signal })
export const fetchSession = (id: string) => apiRequest<ApiSuccess<{ session: TableSession }>>(`/api/table-sessions/${id}`)
export const createOrder = (body: { tableId: string; assignedWaiterId?: string; clientOperationId: string; items: NewOrderItem[] }) => apiRequest<ApiSuccess<{ order: Order }>>('/api/orders', { method: 'POST', body })
export const transferWaiter = (sessionId: string, waiterId: string, reason?: string) => apiRequest<ApiSuccess<{ session: TableSession }>>(`/api/table-sessions/${sessionId}/transfer-waiter`, { method: 'POST', body: { waiterId, reason } })
export const moveSession = (sessionId: string, targetTableId: string, reason?: string) => apiRequest<ApiSuccess<{ session: TableSession }>>(`/api/table-sessions/${sessionId}/move`, { method: 'POST', body: { targetTableId, reason } })
export const cancelOrder = (id: string, reason: string) => apiRequest<ApiSuccess<{ order: Order }>>(`/api/orders/${id}/cancel`, { method: 'POST', body: { reason } })
export const cancelItem = (id: string, reason: string) => apiRequest(`/api/order-items/${id}/cancel`, { method: 'POST', body: { reason } })
export const createStaff = (name: string) => apiRequest('/api/staff', { method: 'POST', body: { name } })
export const updateStaff = (id: string, body: { name?: string; isActive?: boolean }) => apiRequest(`/api/staff/${id}`, { method: 'PUT', body })
