import React from 'react';
import { GripVertical } from 'lucide-react';

interface DragHandleProps {
  className?: string;
}

export const DragHandle: React.FC<DragHandleProps> = ({ className = '' }) => {
  return (
    <div className={`cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 transition-colors ${className}`}>
      <GripVertical size={16} />
    </div>
  );
};