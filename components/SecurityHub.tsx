import React, { useState, useEffect } from 'react';
import { SecurityLog } from '../types';
import { Shield, ShieldAlert, Globe, Lock, Activity, EyeOff, FileSearch, KeyRound, AlertTriangle, Scan, X } from 'lucide-react';

const SecurityHub: React.FC = () => {
  const [firewallEnabled, setFirewallEnabled] = useState(true);
  const [spamProtection, setSpamProtection] = useState(true);

  // Granular Security Controls
  const [blockSqli, setBlockSqli] = useState(true);
  const [blockXss, setBlockXss] = useState(true);
  const [simulationMode, setSimulationMode] = useState(false);

  const [loginEnabled, setLoginEnabled] = useState(true);
  const [loginMaxRetries, setLoginMaxRetries] = useState(3);

  const [scanning, setScanning] = useState(false);
  const [logs, setLogs] = useState<SecurityLog[]>([]);
  const [lastScan, setLastScan] = useState<string>('Never');
  const [lastScanSource, setLastScanSource] = useState<string>('auto');

  // Deep Scan State
  const [showDeepScanModal, setShowDeepScanModal] = useState(false);
  const [deepScanStatus, setDeepScanStatus] = useState<any>(null);

  const { apiUrl, nonce, homeUrl } = window.woosuiteData || {};

  useEffect(() => {
    if (!apiUrl) return;
    fetchStatus();
    fetchLogs();
    fetchDeepScanStatus();
  }, [apiUrl]);

  // Poll Deep Scan if running
  useEffect(() => {
    let interval: any;
    if (deepScanStatus?.status === 'running') {
        interval = setInterval(fetchDeepScanStatus, 3000);
    }
    return () => clearInterval(interval);
  }, [deepScanStatus?.status]);

  const fetchStatus = async () => {
    try {
        const res = await fetch(`${apiUrl}/security/status`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            setFirewallEnabled(data.firewall_enabled);
            setSpamProtection(data.spam_enabled);

            // Set granular options
            setBlockSqli(data.block_sqli);
            setBlockXss(data.block_xss);
            setSimulationMode(data.simulation_mode);

            setLoginEnabled(data.login_enabled);
            setLoginMaxRetries(data.login_max_retries || 3);

            setLastScan(data.last_scan);
            setLastScanSource(data.last_scan_source || 'auto');
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

  const fetchDeepScanStatus = async () => {
    try {
        const res = await fetch(`${apiUrl}/security/deep-scan/status`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            setDeepScanStatus(data);
        }
    } catch (e) {
        console.error("Failed to fetch deep scan status", e);
    }
  };

  const handleToggle = async (option: string, value: boolean) => {
      // Optimistic update
      if (option === 'firewall') setFirewallEnabled(value);
      if (option === 'spam') setSpamProtection(value);
      if (option === 'block_sqli') setBlockSqli(value);
      if (option === 'block_xss') setBlockXss(value);
      if (option === 'simulation_mode') setSimulationMode(value);
      if (option === 'login') setLoginEnabled(value);

      try {
          await fetch(`${apiUrl}/security/toggle`, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-WP-Nonce': nonce
              },
              body: JSON.stringify({ option, value })
          });

          fetchStatus();
      } catch (e) {
          console.error("Failed to toggle option", e);
          fetchStatus(); // Revert on error
      }
  };

  const saveLoginSettings = async (retries: number) => {
    setLoginMaxRetries(retries);
    try {
        await fetch(`${apiUrl}/settings`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
            body: JSON.stringify({ loginMaxRetries: retries })
        });
    } catch (e) {
        console.error("Failed to save login settings", e);
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
            fetchStatus();
        }
    } catch (e) {
        console.error("Scan failed", e);
    } finally {
        setScanning(false);
    }
  };

  const startDeepScan = async () => {
    setShowDeepScanModal(false);
    setDeepScanStatus({ status: 'running', message: 'Starting...', processed_folders: 0, total_folders: 1 }); // Optimistic
    try {
        await fetch(`${apiUrl}/security/deep-scan/start`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce }
        });
        // The worker starts in background. We poll status.
        fetchDeepScanStatus();
    } catch (e) {
        console.error("Deep scan failed to start", e);
        setDeepScanStatus({ status: 'error', message: 'Failed to start scan.' });
    }
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Security & Firewall</h2>
            <p className="text-gray-500">Real-time threat monitoring, malware scanning, and spam protection.</p>
        </div>
        <div className="flex gap-2">
            <a
                href={`${homeUrl}/?test_waf_block=<script>alert(1)</script>`}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 transition flex items-center gap-2 shadow-sm"
            >
                <ShieldAlert size={18} /> Test Firewall
            </a>

            <button
                onClick={() => setShowDeepScanModal(true)}
                className="bg-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-purple-700 transition flex items-center gap-2 shadow-sm"
            >
                <Scan size={18} /> Deep Scan
            </button>

            <div className="flex flex-col items-end gap-1">
                <button
                    onClick={handleScan}
                    disabled={scanning}
                    className="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm disabled:opacity-75"
                    title="Runs a Core Integrity Check (Quick Scan)"
                >
                    {scanning ? <Activity className="animate-spin" size={18} /> : <FileSearch size={18} />}
                    {scanning ? 'Scanning...' : 'Run Quick Scan'}
                </button>
                <span className="text-[10px] text-gray-400 font-medium">Auto-scan every 12h</span>
            </div>
        </div>
      </div>

      {/* Deep Scan Modal */}
      {showDeepScanModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 animate-in fade-in duration-200">
            <div className="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl scale-100 transform transition-all">
                <h3 className="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <AlertTriangle className="text-amber-500" /> Deep Malware Scan
                </h3>
                <p className="text-gray-600 mb-6">
                    This process will recursively scan all files in your <strong>plugins</strong> and <strong>themes</strong> directories for known malware patterns (e.g., eval, base64_decode, shell_exec).
                    <br/><br/>
                    <strong>Warning:</strong> This process is resource-intensive and may temporarily slow down your website.
                </p>
                <div className="flex justify-end gap-3">
                    <button
                        onClick={() => setShowDeepScanModal(false)}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={startDeepScan}
                        className="px-4 py-2 bg-purple-600 text-white hover:bg-purple-700 rounded-lg font-medium shadow-md"
                    >
                        Start Scan
                    </button>
                </div>
            </div>
        </div>
      )}

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
          <p className="text-xs text-gray-500 mt-1">{spamProtection ? 'Honeypot + Link Filter' : 'Disabled'}</p>
        </div>

        {/* Malware Card */}
        <div className="p-5 rounded-xl border border-gray-200 bg-white">
          <div className="flex justify-between items-start mb-2">
             <FileSearch size={24} className="text-purple-600" />
             <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Clean</span>
          </div>
          <h3 className="font-bold text-gray-800">File Integrity</h3>
          <div className="mt-1">
             <p className="text-xs text-gray-500">Last scan: {lastScan}</p>
             <p className="text-[10px] text-gray-400 uppercase tracking-wide font-medium mt-0.5">
                Next Auto-Scan: 12h
             </p>
          </div>
        </div>

        {/* Login Security Card */}
        <div className={`p-5 rounded-xl border transition-all cursor-pointer ${loginEnabled ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white'}`}
             onClick={() => handleToggle('login', !loginEnabled)}>
          <div className="flex justify-between items-start mb-2">
             <KeyRound size={24} className={loginEnabled ? 'text-amber-600' : 'text-gray-400'} />
             <div className={`w-10 h-5 rounded-full p-1 transition-colors ${loginEnabled ? 'bg-amber-500' : 'bg-gray-300'}`}>
              <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${loginEnabled ? 'translate-x-5' : ''}`} />
            </div>
          </div>
          <h3 className="font-bold text-gray-800">Login Security</h3>
          <p className="text-xs text-gray-500 mt-1">{loginEnabled ? `Limit: ${loginMaxRetries} attempts` : 'Disabled'}</p>
        </div>
      </div>

      {/* Deep Scan Progress/Results */}
      {deepScanStatus && deepScanStatus.status !== 'idle' && (
         <div className="bg-white rounded-xl shadow-sm border border-purple-100 p-6 animate-in fade-in slide-in-from-top-4">
            <div className="flex justify-between items-center mb-4">
                 <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                    <Scan size={20} className="text-purple-600"/> Deep Scan Status
                </h3>
                <span className={`px-2 py-1 rounded text-xs font-medium uppercase ${deepScanStatus.status === 'running' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>
                    {deepScanStatus.status}
                </span>
            </div>

            <div className="mb-4">
                <div className="flex justify-between text-sm text-gray-600 mb-1">
                    <span>{deepScanStatus.message}</span>
                    <span>{deepScanStatus.processed_folders} / {deepScanStatus.total_folders} Folders</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                    <div
                        className="bg-purple-600 h-2.5 rounded-full transition-all duration-500"
                        style={{ width: `${deepScanStatus.total_folders > 0 ? (deepScanStatus.processed_folders / deepScanStatus.total_folders) * 100 : 0}%` }}
                    ></div>
                </div>
            </div>

            {deepScanStatus.results && deepScanStatus.results.length > 0 && (
                <div className="mt-6">
                    <h4 className="font-medium text-red-600 mb-2 flex items-center gap-2"><AlertTriangle size={16}/> Suspicious Files Found</h4>
                    <div className="bg-red-50 border border-red-100 rounded-lg overflow-hidden">
                        <table className="w-full text-left text-sm">
                             <thead className="bg-red-100/50 text-red-800">
                                <tr>
                                    <th className="p-3">File</th>
                                    <th className="p-3">Issue</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-red-100">
                                {deepScanStatus.results.map((res: any, idx: number) => (
                                    <tr key={idx}>
                                        <td className="p-3 font-mono text-xs text-gray-700 break-all">{res.file}</td>
                                        <td className="p-3 text-red-700 text-xs font-bold">{res.issue}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {deepScanStatus.status === 'complete' && (!deepScanStatus.results || deepScanStatus.results.length === 0) && (
                <p className="text-green-600 font-medium text-sm flex items-center gap-2">
                    <Shield size={16}/> No malware patterns found. Your site appears clean.
                </p>
            )}
         </div>
      )}

      {/* Firewall Configuration Panel */}
      {firewallEnabled && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 animate-in fade-in slide-in-from-top-4 duration-300">
            <div className="flex justify-between items-center mb-6">
                <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                    <Shield size={20} className="text-indigo-600"/> Firewall Configuration
                </h3>
                <div className="flex items-center gap-3 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-200">
                     <span className="text-sm font-medium text-gray-600">Simulation Mode</span>
                     <button
                        onClick={() => handleToggle('simulation_mode', !simulationMode)}
                        className={`w-10 h-5 rounded-full p-1 transition-colors focus:outline-none ${simulationMode ? 'bg-amber-500' : 'bg-gray-300'}`}
                     >
                        <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${simulationMode ? 'translate-x-5' : ''}`} />
                     </button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 hover:border-indigo-200 transition-colors">
                    <div>
                        <span className="block font-medium text-gray-800">Block SQL Injection</span>
                        <span className="text-xs text-gray-500">Prevents database manipulation attacks (SQLi)</span>
                    </div>
                    <button
                        onClick={() => handleToggle('block_sqli', !blockSqli)}
                        className={`w-10 h-5 rounded-full p-1 transition-colors focus:outline-none ${blockSqli ? 'bg-indigo-600' : 'bg-gray-300'}`}
                    >
                        <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${blockSqli ? 'translate-x-5' : ''}`} />
                    </button>
                </div>

                <div className="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200 hover:border-indigo-200 transition-colors">
                    <div>
                        <span className="block font-medium text-gray-800">Block XSS</span>
                        <span className="text-xs text-gray-500">Prevents Cross-Site Scripting attacks</span>
                    </div>
                    <button
                        onClick={() => handleToggle('block_xss', !blockXss)}
                        className={`w-10 h-5 rounded-full p-1 transition-colors focus:outline-none ${blockXss ? 'bg-indigo-600' : 'bg-gray-300'}`}
                    >
                        <div className={`bg-white w-3 h-3 rounded-full shadow-md transform transition-transform ${blockXss ? 'translate-x-5' : ''}`} />
                    </button>
                </div>
            </div>

            {simulationMode && (
                <div className="mt-4 p-4 bg-amber-50 text-amber-800 text-sm rounded-lg border border-amber-100 flex items-start gap-3">
                    <AlertTriangle size={18} className="mt-0.5 shrink-0" />
                    <div>
                        <p className="font-semibold">Simulation Mode Active</p>
                        <p className="text-amber-700/80 mt-0.5">Threats will be logged but <strong>NOT blocked</strong>. Use this to safely test the firewall rules without affecting legitimate users.</p>
                    </div>
                </div>
            )}
        </div>
      )}

      {/* Login Configuration Panel */}
      {loginEnabled && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 animate-in fade-in slide-in-from-top-4 duration-300">
             <div className="flex justify-between items-center mb-6">
                <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                    <KeyRound size={20} className="text-amber-600"/> Login Security Configuration
                </h3>
            </div>
            <div className="flex items-center gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div className="flex-1">
                    <label className="block text-sm font-medium text-gray-800">Max Login Retries</label>
                    <p className="text-xs text-gray-500">Number of failed attempts allowed before a 15-minute lockout.</p>
                </div>
                <div className="flex items-center gap-2">
                    <input
                        type="number"
                        min="1" max="10"
                        value={loginMaxRetries}
                        onChange={(e) => saveLoginSettings(parseInt(e.target.value))}
                        className="w-20 p-2 border border-gray-300 rounded-lg text-center font-bold text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                    />
                    <span className="text-sm text-gray-500">attempts</span>
                </div>
            </div>
        </div>
      )}

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
