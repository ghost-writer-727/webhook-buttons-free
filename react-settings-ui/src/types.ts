export interface Button {
  id: string;
  enabled: boolean;
  title: string;
  url: string;
  message: string;
  selectOption: 'Confirmation' | 'Just Send';
  // Pro fields (absent in the Lite build; kept in the paid build even when the license lapses)
  visibleToUsers?: string[];
  allowUserNotes?: boolean;
  flatten?: boolean;
  authHeaderName?: string;
  authHeaderValue?: string;
}

export interface MetaBox {
  id: string;
  title: string;
  buttons: Button[];
  visibleToUsers?: string[]; // Pro
}

export interface SaveState {
  hasChanges: boolean;
  isSaving: boolean;
}

export interface AdvancedSettings {
  general: {
    excludeMeta: string[];
  };
  metaKeys?: string[]; // Pro
}

export interface SettingsData {
  metaBoxes?: MetaBox[];
  advanced?: AdvancedSettings;
}

export interface User {
  id: string;
  name: string;
  role: string;
}

// Props of the Pro panels. Declared here so the Lite stub (src/pro/index.lite.ts) can type its nulls.
export interface ProButtonFieldsProps {
  button: Button;
  onChange: (patch: Partial<Button>) => void;
}

export interface ProBoxFieldsProps {
  box: MetaBox;
  onChange: (patch: Partial<MetaBox>) => void;
}

export interface ProAdvancedSectionsProps {
  settings: AdvancedSettings;
  onChange: (patch: Partial<AdvancedSettings>) => void;
}

export interface SampleModalProps {
  isOpen: boolean;
  onClose: () => void;
  button: Button;
  buttonTitle: string;
}
