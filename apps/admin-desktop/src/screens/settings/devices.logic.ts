import type { ConnectivitySnapshot } from '../../app/connectivity.logic'

export interface DevicesViewState {
  mode: 'checking' | 'error' | 'empty' | 'list'
  message: string | null
  canLink: boolean
}

export function devicesViewState(
  snapshot: ConnectivitySnapshot,
  loading: boolean,
  loadError: string | null,
  deviceCount: number,
): DevicesViewState {
  const coreUnavailable = snapshot.components.core === 'OFFLINE' || snapshot.components.core === 'ERROR'
  const message = coreUnavailable
    ? 'No se pueden consultar los dispositivos mientras Restaurant Core está desconectado.'
    : snapshot.components.database === 'OFFLINE'
      ? 'No se pueden consultar los dispositivos porque la base de datos local no está disponible.'
      : loadError
  if (message) return { mode: 'error', message, canLink: false }
  if (loading || snapshot.components.core === 'CHECKING') {
    return { mode: 'checking', message: null, canLink: false }
  }
  return {
    mode: deviceCount === 0 ? 'empty' : 'list',
    message: null,
    canLink: snapshot.localOperationAvailable,
  }
}
