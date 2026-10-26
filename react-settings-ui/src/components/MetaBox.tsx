import React, { useState, Suspense } from 'react';
import { Trash2, ChevronDown, ChevronRight, GripVertical, Plus } from 'lucide-react';
import { MetaBox as MetaBoxType, Button as ButtonType } from '../types';
import { Button as ButtonComponent } from './Button';
import { ProBoxFields } from '../pro';

interface MetaBoxProps {
  group: MetaBoxType;
  index: number;
  onUpdate: (index: number, group: MetaBoxType) => void;
  onDelete: (index: number) => void;
  onDragStart: (e: React.DragEvent, index: number) => void;
  onDragOver: (e: React.DragEvent) => void;
  onDrop: (e: React.DragEvent, dropIndex: number) => void;
  isDragging: boolean;
}

export const newButton = (): ButtonType => ({
  id: `button-${Date.now()}${Math.floor(Math.random() * 10000)}`,
  enabled: false,
  title: '',
  url: '',
  message: '',
  selectOption: 'Confirmation'
});

export const MetaBox: React.FC<MetaBoxProps> = ({
  group,
  index,
  onUpdate,
  onDelete,
  onDragStart,
  onDragOver,
  onDrop,
  isDragging
}) => {
  // New boxes (no title yet) open expanded
  const [isExpanded, setIsExpanded] = useState(() => !group.title);
  const [draggedButtonIndex, setDraggedButtonIndex] = useState<number | null>(null);

  const change = (patch: Partial<MetaBoxType>) => onUpdate(index, { ...group, ...patch });

  const addButton = () => change({ buttons: [...group.buttons, newButton()] });

  const updateButton = (buttonIndex: number, updatedButton: ButtonType) => {
    const updatedButtons = [...group.buttons];
    updatedButtons[buttonIndex] = updatedButton;
    change({ buttons: updatedButtons });
  };

  const deleteButton = (buttonIndex: number) => change({ buttons: group.buttons.filter((_, i) => i !== buttonIndex) });

  // Drag and drop handlers for buttons
  const handleButtonDragStart = (e: React.DragEvent, buttonIndex: number) => {
    setDraggedButtonIndex(buttonIndex);
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleButtonDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  };

  const handleButtonDrop = (e: React.DragEvent, dropButtonIndex: number) => {
    e.preventDefault();
    if (draggedButtonIndex === null || draggedButtonIndex === dropButtonIndex) {
      setDraggedButtonIndex(null);
      return;
    }
    const updatedButtons = [...group.buttons];
    const draggedItem = updatedButtons[draggedButtonIndex];
    updatedButtons.splice(draggedButtonIndex, 1);
    updatedButtons.splice(dropButtonIndex, 0, draggedItem);
    change({ buttons: updatedButtons });
    setDraggedButtonIndex(null);
  };

  return (
    <div
      className={`border border-gray-200 rounded-md overflow-hidden bg-white ${isDragging ? 'opacity-50 scale-98' : ''}`}
      onDragOver={onDragOver}
      onDrop={(e) => onDrop(e, index)}
    >
      <div className="p-4 bg-gray-50 border-b border-gray-200 flex items-center gap-2">
        <div
          className="cursor-grab active:cursor-grabbing p-1 hover:bg-gray-200 rounded"
          draggable
          onDragStart={(e) => onDragStart(e, index)}
        >
          <GripVertical size={18} className="text-gray-500" />
        </div>

        <button
          type="button"
          className="p-1 hover:bg-gray-200 rounded text-gray-500"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          {isExpanded ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
        </button>

        <input
          type="text"
          className="flex-grow px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Box title (shown on the order screen)"
          value={group.title}
          onChange={(e) => change({ title: e.target.value })}
        />

        <div className="flex gap-2">
          <button
            type="button"
            className="p-1.5 bg-red-100 text-red-600 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500"
            onClick={() => onDelete(index)}
            title="Delete box"
            aria-label="Delete box"
          >
            <Trash2 size={16} />
          </button>
        </div>
      </div>

      {isExpanded && (
        <div className="p-4">
          {ProBoxFields && (
            <Suspense fallback={null}>
              <ProBoxFields box={group} onChange={change} />
            </Suspense>
          )}

          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <h3 className="text-sm font-medium text-gray-700">Buttons</h3>
              <button
                type="button"
                className="px-2 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 flex items-center gap-1 text-sm"
                onClick={addButton}
                title="Add button"
              >
                <Plus size={14} />
                Add button
              </button>
            </div>

            <div className="space-y-3">
              {group.buttons.map((button, buttonIndex) => (
                <ButtonComponent
                  key={button.id}
                  group={button}
                  index={buttonIndex}
                  onUpdate={updateButton}
                  onDelete={deleteButton}
                  onDragStart={(e) => handleButtonDragStart(e, buttonIndex)}
                  onDragOver={handleButtonDragOver}
                  onDrop={(e) => handleButtonDrop(e, buttonIndex)}
                  isDragging={draggedButtonIndex === buttonIndex}
                />
              ))}

              {group.buttons.length === 0 && (
                <div className="text-center py-6 bg-gray-50 border border-dashed border-gray-300 rounded-md">
                  <p className="text-sm text-gray-500">No buttons yet</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
