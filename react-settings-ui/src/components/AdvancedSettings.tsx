import React, { useState, useEffect, Suspense } from 'react';
import { AlertCircle, Save, Undo2 } from 'lucide-react';
import { AdvancedSettings as AdvancedSettingsType } from '../types';
import { fetchSettings, sendSettingsToEndpoint } from '../utils/api';
import { MultiSelect } from './MultiSelect';
import { LoadingSpinner } from './LoadingSpinner';
import { ProAdvancedSections } from '../pro';

interface WordPressWindow extends Window {
  webhookButtonsSettings?: { meta_fields?: Record<string, string> };
}

export const AdvancedSettings: React.FC = () => {
  const [settings, setSettings] = useState<AdvancedSettingsType>({ general: { excludeMeta: [] } });
  const [originalSettings, setOriginalSettings] = useState<AdvancedSettingsType>({ general: { excludeMeta: [] } });

  // Product attribute taxonomies, from the localized variable
  const metaFields: Record<string, string> = ((window as WordPressWindow).webhookButtonsSettings?.meta_fields || {});

  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Fetch settings data on component mount
  useEffect(() => {
    const loadSettings = async () => {
      setIsLoading(true);
      setError(null);
      
      try {
        const response = await fetchSettings();
        
        // If the response contains advanced settings, update the state
        if (response.success && response.settings && response.settings.advanced) {
          setSettings(response.settings.advanced);
          setOriginalSettings(JSON.parse(JSON.stringify(response.settings.advanced)));
        }
      } catch (err) {
        console.error('Failed to fetch advanced settings:', err);
        setError('Failed to load advanced settings. Please refresh the page to try again.');
      } finally {
        setIsLoading(false);
      }
    };
    
    loadSettings();
  }, []);
  
  // Check for changes whenever settings change
  useEffect(() => {
    const hasChanges = JSON.stringify(settings) !== JSON.stringify(originalSettings);
    setHasChanges(hasChanges);
  }, [settings, originalSettings]);

  const handleGeneralChange = (field: keyof typeof settings.general, value: string[]) => {
    setSettings(prev => ({
      ...prev,
      general: {
        ...prev.general,
        [field]: value
      }
    }));
  };

  const handleSave = async () => {
    setIsSaving(true);
    
    try {
      // Send advanced settings to the endpoint
      const result = await sendSettingsToEndpoint({ advanced: settings });

      // Get the settings returned from the send Settings endpoint
      // Check if the response has the expected structure
      if (result.success && result.settings && result.settings.advanced) {
        // Update the current data with the returned settings
        setSettings(result.settings.advanced);
        
        // Update original settings to match current settings
        setOriginalSettings(JSON.parse(JSON.stringify(result.settings.advanced)));
        setNotice('Settings saved.');
        setError(null);
      } else {
        console.error('Unexpected API response structure:', result);
        setError('The server sent unexpected data. Please refresh and try again.');
      }
    } catch (error) {
      console.error('Failed to save advanced settings:', error);
      setError('Could not save settings. Please try again.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleUndo = () => {
    setSettings(JSON.parse(JSON.stringify(originalSettings)));
  };

  return (
    <div className="min-h-screen bg-[#f0f0f1] py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        {notice && !error && (
          <div className="mb-6 border-l-4 border-[#00a32a] bg-[#edfaef] px-4 py-3 text-sm text-[#1d2327]" role="status">{notice}</div>
        )}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 p-4 rounded-md flex items-start gap-3">
            <AlertCircle size={20} className="text-red-500 mt-0.5 flex-shrink-0" />
            <div>
              <h3 className="text-sm font-medium text-red-800">Error</h3>
              <p className="text-sm text-red-700 mt-1">{error}</p>
            </div>
          </div>
        )}
        
        {isLoading ? (
          <LoadingSpinner message="Loading advanced settings..." />
        ) : (
          <div className="bg-white shadow-sm border border-gray-200 overflow-hidden">
          <div className="bg-white border-b border-gray-200 px-8 py-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Save className="text-[#2271b1]" size={24} />
                <div>
                  <h1 className="text-2xl font-bold text-[#1d2327]">Advanced Settings</h1>
                  <p className="text-gray-600 mt-1">Extra options for the data each button sends</p>
                </div>
              </div>
              
              {/* Always visible save buttons */}
              <div className="flex items-center gap-3 flex-shrink-0">
                <button
                  onClick={handleUndo}
                  disabled={!hasChanges}
                  className={`inline-flex items-center gap-2 px-4 py-2 font-medium transition-colors shadow-sm ${
                    hasChanges
                      ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                  }`}
                  title={hasChanges ? "Undo changes" : "No changes to undo"}
                >
                  <Undo2 size={18} />
                  Undo changes
                </button>
                <button
                  onClick={handleSave}
                  disabled={isSaving || !hasChanges}
                  className={`inline-flex items-center gap-2 px-4 py-2 font-medium transition-colors shadow-sm ${
                    hasChanges && !isSaving
                      ? 'bg-[#2271b1] text-[#FFFFFF] hover:bg-[#135e96]'
                      : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  }`}
                  title={hasChanges ? "Save changes" : "All changes saved"}
                >
                  {isSaving ? (
                    <div className="animate-spin">
                      <Save size={18} />
                    </div>
                  ) : (
                    <Save size={18} />
                  )}
                  {isSaving ? 'Saving...' : 'Save changes'}
                </button>
              </div>
            </div>
          </div>

          <div className="p-8 space-y-8">
            {/* General Section */}
            <div className="bg-[#f0f0f1] border border-gray-200 p-6">
              <h2 className="text-xl font-semibold text-[#1d2327] mb-6">General</h2>
              
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-medium text-[#1d2327] mb-2">
                    Exclude product attributes
                  </label>
                  <MultiSelect
                    options={Object.entries(metaFields).map(([value, label]) => ({ value: `pa_${value}`, label }))}
                    value={settings.general.excludeMeta}
                    onChange={(value: string[]) => handleGeneralChange('excludeMeta', value)}
                    placeholder="Select attributes to leave out..."
                    className="w-full"
                  />
                  <p className="text-sm text-gray-600 mt-1">
                    These product attributes are not sent.
                  </p>
                </div>
              </div>
            </div>

            {ProAdvancedSections && (
              <Suspense fallback={null}>
                <ProAdvancedSections settings={settings} onChange={(patch) => setSettings(prev => ({ ...prev, ...patch }))} />
              </Suspense>
            )}

            {/* Save Status */}
            {!hasChanges && (
              <div className="bg-green-50 border border-green-200 p-4 flex items-center gap-3">
                <Save size={16} className="text-[#2271b1]" />
                <span className="text-sm font-medium text-green-800">All settings saved</span>
              </div>
            )}
          </div>
          </div>
        )}
      </div>
    </div>
  );
};