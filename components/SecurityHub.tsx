import React, { useState } from 'react';
import { SecurityLog } from '../types';
import { Shield, ShieldAlert, Globe, Lock, Activity, EyeOff, FileSearch, KeyRound } from 'lucide-react';

const mockLogs: SecurityLog[] = [
  { id: 1, ip: '192.168.1.105', event: 'SQL Injection Attempt', timestamp: '2 mins ago', severity: 'high', blocked: true },
  { id: 2, ip: '45.32.11.90', event: 'Brute Force Login', timestamp: '15 mins ago', severity: 'medium', blocked: true },
  { id: 3, ip: '10.0.0.5', event: 'XSS Probe', timestamp: '1 hour ago', severity: 'low', blocked: false },
  { id: 4, ip: '203.11.44.2', event: 'Directory Traversal', timestamp: '3 hours ago', severity: 'high', blocked: true },
  { id: 5, ip: '112.44.22.11', event: 'Spam Comment', timestamp: '5 hours ago', severity: 'medium', blocked: true },
];

const SecurityHub: React.FC = () => {
  const [firewallEnabled, setFirewallEnabled] = useState(true);
  const [spamProtection, setSpamProtection] = useState(true);
  const [scanning, setScanning] = useState(false);

  const handleScan = () => {
    setScanning(true);
    setTimeout(() => setScanning(false), 2000);
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Security & Firewall</h2>
            <p className="text-gray-500">Real-time threat monitoring, malware scanning, and spam protection.</p>
        </div>
        <button 
            onClick={handleScan}
            disabled={scanning}
            className="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm disabled:opacity-75"
        >
            {scanning ? <Activity className="animate-spin" size={18} /> : <FileSearch size={18} />}
            {scanning ? 'Scanning Files...' : 'Run Malware Scan'}
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* WAF Card */}
        <div className={`p-5 rounded-xl border transition-all cursor-pointer ${firewallEnabled ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white'}`}
             onClick={() => setFirewallEnabled(!firewallEnabled)}>
          <div className="flex justify-between items-start mb-2">
            <Shield size={24} className={firewallEnabled ? 'text-green-600' : 'text-gray-400'} />
            <div className={`w-10 h-5 rounded-full p-1 transition-colors ${firewallEnabled ? 'bg-green-500' : 'bg-gray-300'}`}>
              <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${firewallEnabled ? 'translate-x-5' : ''}`} />
            </div>
          </div>
          <h3 className="font-bold text-gray-800">Firewall (WAF)</h3>
          <p className="text-xs text-gray-500 mt-1">{firewallEnabled ? 'Active Protection' : 'Disabled'}</p>
        </div>

        {/* Spam Card */}
        <div className={`p-5 rounded-xl border transition-all cursor-pointer ${spamProtection ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white'}`}
             onClick={() => setSpamProtection(!spamProtection)}>
          <div className="flex justify-between items-start mb-2">
             <EyeOff size={24} className={spamProtection ? 'text-blue-600' : 'text-gray-400'} />
             <div className={`w-10 h-5 rounded-full p-1 transition-colors ${spamProtection ? 'bg-blue-500' : 'bg-gray-300'}`}>
              <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${spamProtection ? 'translate-x-5' : ''}`} />
            </div>
          </div>
          <h3 className="font-bold text-gray-800">Anti-Spam</h3>
          <p className="text-xs text-gray-500 mt-1">{spamProtection ? 'Filtering Bots' : 'Disabled'}</p>
        </div>

        {/* Malware Card */}
        <div className="p-5 rounded-xl border border-gray-200 bg-white">
          <div className="flex justify-between items-start mb-2">
             <FileSearch size={24} className="text-purple-600" />
             <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Clean</span>
          </div>
          <h3 className="font-bold text-gray-800">File Integrity</h3>
          <p className="text-xs text-gray-500 mt-1">Last scan: 2 mins ago</p>
        </div>

        {/* Login Security Card */}
        <div className="p-5 rounded-xl border border-gray-200 bg-white">
          <div className="flex justify-between items-start mb-2">
             <KeyRound size={24} className="text-amber-600" />
             <span className="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">2FA On</span>
          </div>
          <h3 className="font-bold text-gray-800">Login Security</h3>
          <p className="text-xs text-gray-500 mt-1">Limit: 3 attempts</p>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="p-6 border-b border-gray-100 flex justify-between items-center">
          <h3 className="font-semibold text-gray-800 flex items-center gap-2">
            <Activity size={18} className="text-gray-500"/> Live Threat Log
          </h3>
          <span className="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">Nonce Verified</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="p-4">Timestamp</th>
                <th className="p-4">IP Address</th>
                <th className="p-4">Event Type</th>
                <th className="p-4">Severity</th>
                <th className="p-4">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {mockLogs.map((log) => (
                <tr key={log.id} className="hover:bg-gray-50">
                  <td className="p-4 text-gray-500">{log.timestamp}</td>
                  <td className="p-4 font-mono text-gray-600">{log.ip}</td>
                  <td className="p-4 font-medium text-gray-800">{log.event}</td>
                  <td className="p-4">
                    <span className={`px-2 py-1 rounded text-xs font-medium uppercase
                      ${log.severity === 'high' ? 'bg-red-100 text-red-700' : 
                        log.severity === 'medium' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'}`}>
                      {log.severity}
                    </span>
                  </td>
                  <td className="p-4">
                    {log.blocked ? (
                       <span className="flex items-center text-green-600 font-medium text-xs">
                         <Lock size={12} className="mr-1" /> Blocked
                       </span>
                    ) : (
                       <span className="flex items-center text-gray-500 font-medium text-xs">
                         <Globe size={12} className="mr-1" /> Logged Only
                       </span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default SecurityHub;
