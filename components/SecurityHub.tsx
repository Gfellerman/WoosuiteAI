import React, { useState, useEffect } from 'react';
import { SecurityLog } from '../types';
import { Shield, ShieldAlert, Globe, Lock, Activity, EyeOff, FileSearch, KeyRound } from 'lucide-react';

const SecurityHub: React.FC = () => {
  const [firewallEnabled, setFirewallEnabled] = useState(true);
  const [spamProtection, setSpamProtection] = useState(true);
  const [loginEnabled, setLoginEnabled] = useState(true);
  const [scanning, setScanning] = useState(false);
  const [logs, setLogs] = useState<SecurityLog[]>([]);
  const [lastScan, setLastScan] = useState<string>('Never');

  const { apiUrl, nonce } = window.woosuiteData || {};

  useEffect(() => {
    if (!apiUrl) return;
    fetchStatus();
    fetchLogs();
  }, [apiUrl]);

  const fetchStatus = async () => {
    try {
        const res = await fetch(`${apiUrl}/security/status`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            setFirewallEnabled(data.firewall_enabled);
            setSpamProtection(data.spam_enabled);
            setLoginEnabled(data.login_enabled);
            setLastScan(data.last_scan);
        }
    } catch (e) {
        console.error("Failed to fetch security status", e);
    }
  };

  const fetchLogs = async () => {
    try {
        const res = await fetch(`${apiUrl}/security/logs`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            setLogs(data);
        }
    } catch (e) {
        console.error("Failed to fetch logs", e);
    }
  };

  const handleToggle = async (option: 'firewall' | 'spam', value: boolean) => {
      // Optimistic update
      if (option === 'firewall') setFirewallEnabled(value);
      if (option === 'spam') setSpamProtection(value);

      try {
          await fetch(`${apiUrl}/security/toggle`, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-WP-Nonce': nonce
              },
              body: JSON.stringify({ option, value })
          });
      } catch (e) {
          console.error("Failed to toggle option", e);
          // Revert on error?
          fetchStatus();
      }
  };

  const handleScan = async () => {
    setScanning(true);
    try {
        const res = await fetch(`${apiUrl}/security/scan`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const result = await res.json();
            console.log("Scan result:", result);
            // Refresh status to get new last scan time
            fetchStatus();
        }
    } catch (e) {
        console.error("Scan failed", e);
    } finally {
        setScanning(false);
    }
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
             onClick={() => handleToggle('firewall', !firewallEnabled)}>
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
             onClick={() => handleToggle('spam', !spamProtection)}>
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
          <p className="text-xs text-gray-500 mt-1">Last scan: {lastScan}</p>
        </div>

        {/* Login Security Card */}
        <div className={`p-5 rounded-xl border transition-all ${loginEnabled ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white'}`}>
          <div className="flex justify-between items-start mb-2">
             <KeyRound size={24} className={loginEnabled ? 'text-amber-600' : 'text-gray-400'} />
             <span className={`text-xs px-2 py-0.5 rounded-full ${loginEnabled ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'}`}>
               {loginEnabled ? 'Active' : 'Disabled'}
             </span>
          </div>
          <h3 className="font-bold text-gray-800">Login Security</h3>
          <p className="text-xs text-gray-500 mt-1">Limit: 3 attempts • 15min Lockout</p>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="p-6 border-b border-gray-100 flex justify-between items-center">
          <h3 className="font-semibold text-gray-800 flex items-center gap-2">
            <Activity size={18} className="text-gray-500"/> Security Log
          </h3>
          <span className="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">Real-time</span>
        </div>
        <div className="overflow-x-auto">
          {logs.length === 0 ? (
              <div className="p-8 text-center text-gray-500">
                  <Shield size={32} className="mx-auto mb-2 text-gray-300" />
                  No threats detected yet.
              </div>
          ) : (
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
              {logs.map((log) => (
                <tr key={log.id} className="hover:bg-gray-50">
                  <td className="p-4 text-gray-500">{log.created_at}</td>
                  <td className="p-4 font-mono text-gray-600">{log.ip_address}</td>
                  <td className="p-4 font-medium text-gray-800">{log.event}</td>
                  <td className="p-4">
                    <span className={`px-2 py-1 rounded text-xs font-medium uppercase
                      ${log.severity === 'high' ? 'bg-red-100 text-red-700' : 
                        log.severity === 'medium' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'}`}>
                      {log.severity}
                    </span>
                  </td>
                  <td className="p-4">
                    {(log.blocked == 1 || log.blocked === true) ? (
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
          )}
        </div>
      </div>
    </div>
  );
};

export default SecurityHub;
