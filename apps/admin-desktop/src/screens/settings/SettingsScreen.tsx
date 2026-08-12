import { useState } from 'react'
import { SlidersHorizontal } from 'lucide-react'
import { Tabs } from '../../components/ui/Tabs'
import type { TabItem } from '../../components/ui/Tabs'
import { DiagnosticoPanel } from './DiagnosticoPanel'
import './SettingsScreen.css'

type SettingsSection = 'sistema'

const SECTIONS: TabItem[] = [{ value: 'sistema', label: 'Sistema', icon: <SlidersHorizontal size={16} /> }]

export function SettingsScreen() {
  const [section, setSection] = useState<SettingsSection>('sistema')

  return (
    <div className="settings">
      <h1 className="settings__title">Configuración</h1>
      <Tabs items={SECTIONS} value={section} onChange={(value) => setSection(value as SettingsSection)} />
      <div className="settings__panel">{section === 'sistema' && <DiagnosticoPanel />}</div>
    </div>
  )
}
