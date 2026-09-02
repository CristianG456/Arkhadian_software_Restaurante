import { apiRequest } from './client'
import type { ApiSuccess } from './types'
export interface Printer { id: string; name: string; driverMode: 'TEST_FILE' | 'WINDOWS_RAW_SHARE'; connectionType: string; systemName: string | null; devicePath: string | null; paperWidth: 58 | 80; isActive: boolean; isDefaultFallback: boolean; isPreaccountDefault: boolean }
export interface ProductionCenter { id: string; name: string; sortOrder: number; isActive: boolean; productsCount: number; printers: (Printer & { priority: number })[] }
export interface PrintJob { id: string; jobType: string; status: 'PENDING' | 'PROCESSING' | 'PRINTED' | 'FAILED'; attempts: number; maxAttempts: number; errorCode: string | null; errorMessage: string | null; center: { id: string; name: string } | null; printer: Printer | null; createdAt: string; printedAt: string | null }
export interface Preaccount { restaurantName: string; location: string; waiter: string; createdAt: string; items: { quantity: number; name: string; variant: string | null; modifiers: string[]; lineTotal: string }[]; total: string; notice: string }
export const fetchCenters = () => apiRequest<ApiSuccess<{ centers: ProductionCenter[] }>>('/api/production/centers')
export const fetchPrinters = () => apiRequest<ApiSuccess<{ printers: Printer[] }>>('/api/production/printers')
export const fetchPrintJobs = () => apiRequest<ApiSuccess<{ printJobs: PrintJob[] }>>('/api/production/print-jobs')
export const createCenter = (name: string) => apiRequest('/api/production/centers', { method: 'POST', body: { name } })
export const updateCenter = (id: string, body: { name?: string; isActive?: boolean }) => apiRequest(`/api/production/centers/${id}`, { method: 'PUT', body })
export const assignPrinter = (centerId: string, printerId: string) => apiRequest(`/api/production/centers/${centerId}/printer`, { method: 'PUT', body: { printerId, priority: 1 } })
export const createPrinter = (body: { name: string; driverMode: Printer['driverMode']; connectionType: string; systemName?: string; paperWidth: 58 | 80; isPreaccountDefault?: boolean }) => apiRequest('/api/production/printers', { method: 'POST', body })
export const updatePrinter = (id: string, body: Partial<Pick<Printer, 'name' | 'isActive' | 'isPreaccountDefault'>>) => apiRequest(`/api/production/printers/${id}`, { method: 'PUT', body })
export const testPrinter = (id: string) => apiRequest(`/api/production/printers/${id}/test`, { method: 'POST' })
export const retryPrintJob = (id: string) => apiRequest(`/api/production/print-jobs/${id}/retry`, { method: 'POST' })
export const processNextPrintJob = () => apiRequest('/api/production/print-jobs/process-next', { method: 'POST' })
export const previewPreaccount = (sessionId: string) => apiRequest<ApiSuccess<{ preaccount: Preaccount }>>(`/api/table-sessions/${sessionId}/preaccount`)
export const printPreaccount = (sessionId: string) => apiRequest(`/api/table-sessions/${sessionId}/preaccount/print`, { method: 'POST' })
export const requestBill = (sessionId: string) => apiRequest(`/api/table-sessions/${sessionId}/request-bill`, { method: 'POST' })
export const reprintOrder = (orderId: string, productionCenterId?: string) => apiRequest(`/api/orders/${orderId}/reprint`, { method: 'POST', body: { productionCenterId } })
