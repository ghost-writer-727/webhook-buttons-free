/**
 * Lite (wordpress.org) build: there are no Pro panels. scripts/build.sh puts this file in place of index.ts,
 * so what ships in the Lite zip is exactly what the Lite bundle was built from.
 */
import type { ComponentType } from 'react';
import type { ProButtonFieldsProps, ProBoxFieldsProps, ProAdvancedSectionsProps, SampleModalProps } from '../types';

export const hasPro = false;

export const ProButtonFields: ComponentType<ProButtonFieldsProps> | null = null;
export const ProBoxFields: ComponentType<ProBoxFieldsProps> | null = null;
export const ProAdvancedSections: ComponentType<ProAdvancedSectionsProps> | null = null;
export const SampleModal: ComponentType<SampleModalProps> | null = null;
