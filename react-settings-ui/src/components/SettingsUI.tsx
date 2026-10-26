import React, { useState, useEffect } from 'react';
import { Plus, Send, Save, Undo2, AlertCircle } from 'lucide-react';
import { SettingsData, MetaBox } from '../types';
import { MetaBox as MetaBoxComponent, newButton } from './MetaBox';
import { sendSettingsToEndpoint, fetchSettings } from '../utils/api';
import { LoadingSpinner } from './LoadingSpinner';
import { license, proNotice } from '../utils/license';

export const SettingsUI: React.FC = () => {
  const [settingsData, setSettingsData] = useState<SettingsData>({
    metaBoxes: []
  });
  const [originalData, setOriginalData] = useState<SettingsData>({
    metaBoxes: []
  });
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [hasGlobalChanges, setHasGlobalChanges] = useState(false);
  const [isSavingAll, setIsSavingAll] = useState(false);
  const [notice, setNotice] = useState<{ kind: 'ok' | 'error'; text: string } | null>(null);
  const [savedGroupIds, setSavedGroupIds] = useState<Set<string>>(new Set());
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Fetch settings data on component mount
  useEffect(() => {
    const loadSettings = async () => {
      setIsLoading(true);
      setError(null);
      
      try {
        const response = await fetchSettings();
        
        // If the response contains metaBoxes, update the state
        if (response.success && response.settings && response.settings.metaBoxes) {
          // Create a properly structured settings data object
          const settingsData: SettingsData = {
            metaBoxes: response.settings.metaBoxes
          };
          
          setSettingsData(settingsData);
          setOriginalData(JSON.parse(JSON.stringify(settingsData)));
          
          // Mark all groups as saved
          const allGroupIds = response.settings.metaBoxes.map((group: MetaBox) => group.id);
          setSavedGroupIds(new Set(allGroupIds));
        } else {
          console.error('MetaBoxes not found in API response:', response);
          setError('Failed to load settings: metaBoxes not found in response');
        }
      } catch (err) {
        console.error('Failed to fetch settings:', err);
        setError('Failed to load settings. Please refresh the page to try again.');
      } finally {
        setIsLoading(false);
      }
    };
    
    loadSettings();
  }, []);
  
  // Check for global changes whenever settingsData changes
  useEffect(() => {
    const hasChanges = JSON.stringify(settingsData) !== JSON.stringify(originalData);
    setHasGlobalChanges(hasChanges);
  }, [settingsData, originalData]);

  const addMetaBox = () => {
    const newGroup: MetaBox = {
      id: `metabox-${Date.now()}${Math.floor(Math.random() * 10000)}`,
      title: '',
      buttons: []
    };
    setSettingsData(prev => ({
      ...prev,
      metaBoxes: [...prev.metaBoxes, newGroup]
    }));
  };

  // Empty state: one new box that already holds one new button
  const addFirstButton = () => {
    const newGroup: MetaBox = {
      id: `metabox-${Date.now()}${Math.floor(Math.random() * 10000)}`,
      title: '',
      buttons: [newButton()]
    };
    setSettingsData(prev => ({
      ...prev,
      metaBoxes: [...prev.metaBoxes, newGroup]
    }));
  };

  const updateMetaBox = (index: number, updatedGroup: MetaBox) => {
    setSettingsData(prev => {
      const updatedGroups = [...prev.metaBoxes];
      updatedGroups[index] = updatedGroup;
      return { ...prev, metaBoxes: updatedGroups };
    });
  };

  const deleteMetaBox = (index: number) => {
    const groupToDelete = settingsData.metaBoxes[index];
    setSettingsData(prev => ({
      ...prev,
      metaBoxes: prev.metaBoxes.filter((_, i) => i !== index)
    }));
    
    // Remove from saved groups
    setSavedGroupIds(prev => {
      const newSet = new Set(prev);
      newSet.delete(groupToDelete.id);
      return newSet;
    });
  };

  const saveAllGroups = async () => {
    setIsSavingAll(true);
    
    try {
      // Send all settings to the endpoint
      const response = await sendSettingsToEndpoint(settingsData);

      // Get the settings returned from the send Settings endpoint
      const settings = response.settings; 

      //Update the current data with the returned settings
      setSettingsData(settings);

      // Update original data to match current data
      setOriginalData(JSON.parse(JSON.stringify(settings)));
      
      // Mark all groups as saved
      const allGroupIds = settings.metaBoxes.map((group: MetaBox) => group.id);
      setSavedGroupIds(new Set(allGroupIds));
      setNotice({ kind: 'ok', text: 'Settings saved.' });
    } catch (error) {
      console.error('Failed to save all settings:', error);
      setNotice({ kind: 'error', text: 'Could not save settings. Please try again.' });
    } finally {
      setIsSavingAll(false);
    }
  };

  const undoAllChanges = () => {
    setSettingsData(JSON.parse(JSON.stringify(originalData)));
    // Reset saved group IDs to match original data
    const originalGroupIds = originalData.metaBoxes.map((group: MetaBox) => group.id);
    setSavedGroupIds(new Set(originalGroupIds));
  };

  // Change detection functionality moved to global undo button

  const handleDragStart = (e: React.DragEvent, index: number) => {
    setDraggedIndex(index);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    
    if (draggedIndex === null || draggedIndex === dropIndex) {
      setDraggedIndex(null);
      return;
    }

    setSettingsData(prev => {
      const updatedGroups = [...prev.metaBoxes];
      const draggedItem = updatedGroups[draggedIndex];
      updatedGroups.splice(draggedIndex, 1);
      updatedGroups.splice(dropIndex, 0, draggedItem);
      return { ...prev, metaBoxes: updatedGroups };
    });
    
    setDraggedIndex(null);
  };

  return (
    <div className="py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
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
          <LoadingSpinner message="Loading buttons..." />
        ) : (
          <div className="bg-white shadow-sm border border-gray-200 overflow-hidden">
          <div className="bg-white border-b border-gray-200 px-8 py-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Send className="text-[#2271b1]" size={24} />
                <div>
                  <h1 className="text-2xl font-bold text-[#1d2327]">Webhook Buttons</h1>
                  <p className="text-gray-600 mt-1">Add buttons to the order screen. Each button sends the order to a webhook URL.</p>
                </div>
              </div>
              
              {/* Always visible save buttons */}
              <div className="flex items-center gap-3 flex-shrink-0">
                <button
                  onClick={undoAllChanges}
                  disabled={!hasGlobalChanges}
                  className={`inline-flex items-center gap-2 px-4 py-2 font-medium transition-colors shadow-sm ${
                    hasGlobalChanges
                      ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                  }`}
                  title={hasGlobalChanges ? "Undo all changes" : "No changes to undo"}
                >
                  <Undo2 size={18} />
                  Undo all
                </button>
                <button
                  onClick={saveAllGroups}
                  disabled={isSavingAll || !hasGlobalChanges}
                  className={`inline-flex items-center gap-2 px-4 py-2 font-medium transition-colors shadow-sm ${
                    hasGlobalChanges && !isSavingAll
                      ? 'bg-[#2271b1] text-[#FFFFFF] hover:bg-[#135e96]'
                      : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  }`}
                  title={hasGlobalChanges ? "Save all changes" : "All changes saved"}
                >
                  <Save size={18} />
                  {isSavingAll ? 'Saving...' : 'Save all'}
                </button>
              </div>
            </div>
          </div>

          {notice && (
            <div className={`mb-4 border-l-4 px-4 py-3 text-sm ${notice.kind === 'ok' ? 'border-[#00a32a] bg-[#edfaef] text-[#1d2327]' : 'border-[#d63638] bg-[#fcf0f1] text-[#1d2327]'}`} role="status">
              {notice.text}
            </div>
          )}
          <div className="p-8">
            {proNotice && (
              <p className="mb-6 text-sm text-gray-700">
                {proNotice}{' '}
                {license.upgrade_url && (
                  <a href={license.upgrade_url} className="text-[#2271b1] underline">Upgrade to Pro</a>
                )}
              </p>
            )}
            <div className="flex items-start justify-between mb-8 gap-6">
              <div className="w-1/2">
                <h2 className="text-xl font-semibold text-[#1d2327]">Button boxes</h2>
                <p className="text-sm text-gray-600 mt-1">
                  Each box shows on the order screen with its buttons.
                </p>
              </div>
              <div className="flex items-center gap-3 flex-shrink-0">
                <button
                  onClick={addMetaBox}
                  className="inline-flex items-center gap-2 px-4 py-2 bg-[#2271b1] text-[#FFFFFF] font-medium hover:bg-[#135e96] transition-colors shadow-sm"
                  title="Add box"
                >
                  <Plus size={18} />
                  Add box
                </button>
              </div>
            </div>

            <div className="space-y-6">
              {settingsData.metaBoxes.map((group, index) => (
                <MetaBoxComponent
                  key={group.id}
                  group={group}
                  index={index}
                  onUpdate={updateMetaBox}
                  onDelete={deleteMetaBox}
                  onDragStart={handleDragStart}
                  onDragOver={handleDragOver}
                  onDrop={handleDrop}
                  isDragging={draggedIndex === index}
                />
              ))}

              {settingsData.metaBoxes.length === 0 && (
                <div className="text-center py-16 bg-[#f0f0f1] border-2 border-dashed border-gray-300">
                  <Send className="mx-auto text-gray-400 mb-4" size={48} />
                  <h3 className="text-lg font-medium text-[#1d2327] mb-2">No buttons yet</h3>
                  <p className="text-gray-600 mb-6">Buttons appear in a box on the order screen.</p>
                  <button
                    onClick={addFirstButton}
                    className="inline-flex items-center gap-2 px-6 py-3 bg-[#2271b1] text-[#FFFFFF] font-medium hover:bg-[#135e96] transition-colors shadow-sm"
                    title="Add your first button"
                  >
                    <Plus size={18} />
                    Add your first button
                  </button>
                </div>
              )}
            </div>

            {settingsData.metaBoxes.length > 0 && (
              <div className="mt-8 p-4 bg-[#f0f0f1] border border-gray-200">
                <h3 className="text-sm font-medium text-[#1d2327] mb-2">Tips</h3>
                <ul className="text-sm text-gray-600 space-y-1">
                  <li>• Drag the grip handle to reorder boxes and buttons.</li>
                  <li>• Click the arrow to open or close a box or button.</li>
                  <li>• A button shows on the order screen only when it is enabled and has a webhook URL.</li>
                  <li>• Click "Save all" to keep your changes.</li>
                </ul>
              </div>
            )}
          </div>


        </div>
        )}
      </div>
    </div>
  );
};