import { useCallback, useEffect, useState } from 'react'
import * as api from '../api/production'
import { operationError } from './useOrdersOperation'
export function useProduction() {
  const [centers, setCenters] = useState<api.ProductionCenter[]>([]); const [printers, setPrinters] = useState<api.Printer[]>([]); const [jobs, setJobs] = useState<api.PrintJob[]>([])
  const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false); const [error, setError] = useState<string | null>(null)
  const load = useCallback(async () => { try { const [c, p, j] = await Promise.all([api.fetchCenters(), api.fetchPrinters(), api.fetchPrintJobs()]); setCenters(c.data.centers); setPrinters(p.data.printers); setJobs(j.data.printJobs); setError(null) } catch (cause) { setError(operationError(cause)) } finally { setLoading(false) } }, [])
  useEffect(() => { const id = window.setTimeout(() => void load(), 0); return () => window.clearTimeout(id) }, [load])
  async function mutate<T>(action: () => Promise<T>) { setSaving(true); try { const value = await action(); await load(); return value } finally { setSaving(false) } }
  return { centers, printers, jobs, loading, saving, error, refresh: load,
    createCenter: (name: string) => mutate(() => api.createCenter(name)), updateCenter: (id: string, body: Parameters<typeof api.updateCenter>[1]) => mutate(() => api.updateCenter(id, body)),
    assignPrinter: (center: string, printer: string) => mutate(() => api.assignPrinter(center, printer)), createPrinter: (body: Parameters<typeof api.createPrinter>[0]) => mutate(() => api.createPrinter(body)),
    updatePrinter: (id: string, body: Parameters<typeof api.updatePrinter>[1]) => mutate(() => api.updatePrinter(id, body)), testPrinter: (id: string) => mutate(() => api.testPrinter(id)),
    retry: (id: string) => mutate(() => api.retryPrintJob(id)), process: () => mutate(api.processNextPrintJob),
  }
}
