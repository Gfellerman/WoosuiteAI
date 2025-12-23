import React, { useState } from 'react';
import { Backup } from '../types';
import { Cloud, HardDrive, Download, RotateCcw, CheckCircle } from 'lucide-react';

const mockBackups: Backup[] = [
    { id: 1, name: 'backup_2023_10_27.zip', size: '145 MB', date: 'Oct 27, 2023 - 02:00 AM', location: 'drive' },
    { id: 2, name: 'backup_2023_10_26.zip', size: '142 MB', date: 'Oct 26, 2023 - 02:00 AM', location: 'drive' },
    { id: 3, name: 'backup_2023_10_25.zip', size: '138 MB', date: 'Oct 25, 2023 - 02:00 AM', location: 'local' },
];

const BackupManager: React.FC = () => {
  const [backingUp, setBackingUp] = useState(false);
  const [progress, setProgress] = useState(0);

  const startBackup = () => {
    setBackingUp(true);
    setProgress(0);
    const interval = setInterval(() => {
        setProgress(prev => {
            if (prev >= 100) {
                clearInterval(interval);
                setTimeout(() => setBackingUp(false), 500);
                return 100;
            }
            return prev + 5;
        });
    }, 100);
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Backups & Drive</h2>
            <p className="text-gray-500">Automated daily backups to Google Drive.</p>
        </div>
        <button 
            onClick={startBackup}
            disabled={backingUp}
            className="bg-gray-800 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-900 transition flex items-center gap-2 shadow-lg shadow-gray-200"
        >
            {backingUp ? 'Backing up...' : 'Backup Now'}
        </button>
      </div>

      <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
         <div className="flex items-center gap-4">
             <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                <Cloud size={24} />
             </div>
             <div>
                 <h3 className="font-semibold text-gray-800">Google Drive Connected</h3>
                 <p className="text-sm text-gray-500">Account: admin@woostore.com</p>
             </div>
         </div>
         <div className="text-green-600 flex items-center gap-1 text-sm font-medium">
             <CheckCircle size={16} /> Active
         </div>
      </div>

      {backingUp && (
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 animate-fade-in">
             <div className="flex justify-between text-sm font-medium text-gray-700 mb-2">
                 <span>Creating Database Snapshot...</span>
                 <span>{progress}%</span>
             </div>
             <div className="w-full bg-gray-100 rounded-full h-2.5">
                <div className="bg-purple-600 h-2.5 rounded-full transition-all duration-300" style={{ width: `${progress}%` }}></div>
             </div>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table className="w-full text-left">
          <thead className="bg-gray-50 border-b border-gray-100">
            <tr>
              <th className="p-4 text-sm font-semibold text-gray-600">Filename</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Size</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Date</th>
              <th className="p-4 text-sm font-semibold text-gray-600">Location</th>
              <th className="p-4 text-sm font-semibold text-gray-600 text-right">Restore</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {mockBackups.map((backup) => (
              <tr key={backup.id} className="hover:bg-gray-50">
                <td className="p-4 font-mono text-gray-700 flex items-center gap-2">
                    <HardDrive size={16} className="text-gray-400" />
                    {backup.name}
                </td>
                <td className="p-4 text-gray-600 text-sm">{backup.size}</td>
                <td className="p-4 text-gray-600 text-sm">{backup.date}</td>
                <td className="p-4">
                    {backup.location === 'drive' ? (
                        <span className="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-50 text-green-700 text-xs font-medium border border-green-100">
                            <Cloud size={10} /> Cloud
                        </span>
                    ) : (
                         <span className="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs font-medium border border-gray-200">
                            <HardDrive size={10} /> Local
                        </span>
                    )}
                </td>
                <td className="p-4 text-right flex justify-end gap-2">
                  <button className="text-gray-400 hover:text-blue-600 transition" title="Download">
                    <Download size={16} />
                  </button>
                  <button className="text-gray-400 hover:text-red-600 transition" title="Restore">
                    <RotateCcw size={16} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default BackupManager;
