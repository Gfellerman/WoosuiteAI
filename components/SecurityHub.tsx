import React, { useState, useEffect } from 'react';
import { SecurityLog } from '../types';
import { Shield, ShieldAlert, Globe, Lock, Activity, EyeOff, FileSearch, KeyRound, AlertTriangle, Scan, X, Trash2, Archive, CheckCircle, RotateCcw } from 'lucide-react';

const SecurityHub: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'dashboard' | 'quarantine'>('dashboard');

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

  // Quarantine & Ignore Lists
  const [quarantinedFiles, setQuarantinedFiles] = useState<any[]>([]);
  const [ignoredPaths, setIgnoredPaths] = useState<string[]>([]);

  const { apiUrl, nonce, homeUrl } = (window as any).woosuiteData || {};

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

  // Fetch Lists when tab changes
  useEffect(() => {
      if (activeTab === 'quarantine') {
          fetchQuarantine();
          fetchIgnored();
      }
  }, [activeTab]);

  const fetchStatus = async () => {
    try {
        const res = await fetch(`${apiUrl}/security/status`, {
            headers: { 'X-WP-Nonce': nonce }
        });
        if (res.ok) {
            const data = await res.json();
            setFirewallEnabled(data.firewall_enabled);
            setSpamProtection(data.spam_enabled);
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

  const fetchQuarantine = async () => {
      try {
          const res = await fetch(`${apiUrl}/security/quarantine`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              const data = await res.json();
              setQuarantinedFiles(data.files || []);
          }
      } catch (e) { console.error(e); }
  };

  const fetchIgnored = async () => {
      try {
          const res = await fetch(`${apiUrl}/security/ignore`, {
              headers: { 'X-WP-Nonce': nonce }
          });
          if (res.ok) {
              const data = await res.json();
              setIgnoredPaths(data.ignored || []);
          }
      } catch (e) { console.error(e); }
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
          fetchStatus();
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
        fetchDeepScanStatus();
    } catch (e) {
        console.error("Deep scan failed to start", e);
        setDeepScanStatus({ status: 'error', message: 'Failed to start scan.' });
    }
  };

  // --- Action Handlers ---

  const handleIgnore = async (filepath: string) => {
      try {
          // Identify if it's a plugin or just a file
          // If the path contains 'wp-content/plugins/xyz', we might want to ignore the whole plugin?
          // For now, let's just ignore the file/path provided by the scan result
          const res = await fetch(`${apiUrl}/security/ignore`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ path: filepath })
          });
          if (res.ok) {
              // Remove from local view
              setDeepScanStatus((prev: any) => ({
                  ...prev,
                  results: prev.results.filter((r: any) => r.file !== filepath)
              }));
          }
      } catch (e) { alert("Failed to ignore file."); }
  };

  const handleQuarantine = async (filepath: string) => {
      if (!confirm("Are you sure you want to move this file to quarantine? It will be removed from its current location.")) return;
      try {
          const res = await fetch(`${apiUrl}/security/quarantine/move`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ file: filepath })
          });
          const data = await res.json();
          if (res.ok && data.success) {
               // Remove from local view
               setDeepScanStatus((prev: any) => ({
                  ...prev,
                  results: prev.results.filter((r: any) => r.file !== filepath)
              }));
          } else {
              alert("Error: " + data.message);
          }
      } catch (e) { alert("Failed to quarantine file."); }
  };

  const handleRestore = async (id: string) => {
      try {
          const res = await fetch(`${apiUrl}/security/quarantine/restore`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ id })
          });
          if (res.ok) {
              fetchQuarantine();
          }
      } catch (e) { alert("Failed to restore."); }
  };

  const handleDeleteQuarantine = async (id: string) => {
      if (!confirm("Permanently delete this file? This cannot be undone.")) return;
      try {
          const res = await fetch(`${apiUrl}/security/quarantine/delete`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ id })
          });
          if (res.ok) {
              fetchQuarantine();
          }
      } catch (e) { alert("Failed to delete."); }
  };

  const handleUnIgnore = async (path: string) => {
      try {
          const res = await fetch(`${apiUrl}/security/ignore/remove`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
              body: JSON.stringify({ path })
          });
          if (res.ok) {
              fetchIgnored();
          }
      } catch (e) { alert("Failed to un-ignore."); }
  };

  const closeScanResults = () => {
      setDeepScanStatus({ ...deepScanStatus, status: 'idle' });
  };

  return (
    <div className="space-y-6">
       <div className="flex justify-between items-center">
        <div>
            <h2 className="text-2xl font-bold text-gray-800">Security & Firewall</h2>
            <p className="text-gray-500">Real-time threat monitoring, malware scanning, and spam protection.</p>
        </div>
        <div className="flex gap-2">
            <div className="bg-gray-100 p-1 rounded-lg flex text-sm font-medium">
                <button
                    onClick={() => setActiveTab('dashboard')}
                    className={`px-3 py-1.5 rounded-md transition-all ${activeTab === 'dashboard' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                >
                    Dashboard
                </button>
                <button
                    onClick={() => setActiveTab('quarantine')}
                    className={`px-3 py-1.5 rounded-md transition-all ${activeTab === 'quarantine' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                >
                    Quarantine & Ignored
                </button>
            </div>

            <button
                onClick={() => setShowDeepScanModal(true)}
                className="bg-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-purple-700 transition flex items-center gap-2 shadow-sm ml-2"
            >
                <Scan size={18} /> Deep Scan
            </button>
        </div>
      </div>

      {activeTab === 'dashboard' && (
      <>
        {/* Deep Scan Modal */}
        {showDeepScanModal && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 animate-in fade-in duration-200">
                <div className="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl scale-100 transform transition-all">
                    <h3 className="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                        <AlertTriangle className="text-amber-500" /> Deep Malware Scan
                    </h3>
                    <p className="text-gray-600 mb-6">
                        This process will recursively scan all files in your <strong>plugins</strong> and <strong>themes</strong> directories.
                        <br/><br/>
                        Trusted plugins (WooCommerce, etc.) are automatically skipped to reduce false positives.
                    </p>
                    <div className="flex justify-end gap-3">
                        <button onClick={() => setShowDeepScanModal(false)} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium">Cancel</button>
                        <button onClick={startDeepScan} className="px-4 py-2 bg-purple-600 text-white hover:bg-purple-700 rounded-lg font-medium shadow-md">Start Scan</button>
                    </div>
                </div>
            </div>
        )}

        {/* Deep Scan Progress/Results */}
        {deepScanStatus && deepScanStatus.status !== 'idle' && (
            <div className="bg-white rounded-xl shadow-sm border border-purple-100 p-6 animate-in fade-in slide-in-from-top-4">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                        <Scan size={20} className="text-purple-600"/> Deep Scan Status
                    </h3>
                    <div className="flex items-center gap-2">
                        <span className={`px-2 py-1 rounded text-xs font-medium uppercase ${deepScanStatus.status === 'running' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>
                            {deepScanStatus.status}
                        </span>
                        {deepScanStatus.status === 'complete' && (
                            <button onClick={closeScanResults} className="text-gray-400 hover:text-gray-600 p-1">
                                <X size={20} />
                            </button>
                        )}
                    </div>
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
                        <h4 className="font-medium text-red-600 mb-2 flex items-center gap-2"><AlertTriangle size={16}/> Suspicious Files Found ({deepScanStatus.results.length})</h4>
                        <div className="bg-red-50 border border-red-100 rounded-lg overflow-hidden max-h-96 overflow-y-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-red-100/50 text-red-800 sticky top-0">
                                    <tr>
                                        <th className="p-3">File</th>
                                        <th className="p-3">Issue</th>
                                        <th className="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-red-100">
                                    {deepScanStatus.results.map((res: any, idx: number) => (
                                        <tr key={idx} className="hover:bg-red-100/50 transition-colors">
                                            <td className="p-3 font-mono text-xs text-gray-700 break-all">{res.file}</td>
                                            <td className="p-3 text-red-700 text-xs font-bold whitespace-nowrap">{res.issue}</td>
                                            <td className="p-3 flex justify-end gap-2">
                                                <button
                                                    onClick={() => handleIgnore(res.file)}
                                                    className="px-2 py-1 bg-white border border-gray-300 rounded text-xs text-gray-600 hover:bg-gray-50 flex items-center gap-1"
                                                    title="Ignore this file in future scans"
                                                >
                                                    <EyeOff size={12}/> Ignore
                                                </button>
                                                <button
                                                    onClick={() => handleQuarantine(res.file)}
                                                    className="px-2 py-1 bg-white border border-red-300 text-red-600 rounded text-xs hover:bg-red-50 flex items-center gap-1"
                                                    title="Move to Quarantine"
                                                >
                                                    <Archive size={12}/> Quarantine
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                         <div className="mt-4 flex justify-end">
                            <button
                                onClick={closeScanResults}
                                className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium"
                            >
                                Close Results
                            </button>
                        </div>
                    </div>
                )}

                {deepScanStatus.status === 'complete' && (!deepScanStatus.results || deepScanStatus.results.length === 0) && (
                    <div className="flex flex-col items-center justify-center p-6 bg-green-50 rounded-lg border border-green-100">
                        <CheckCircle size={48} className="text-green-500 mb-2" />
                        <p className="text-green-800 font-medium">Scan Complete</p>
                        <p className="text-green-600 text-sm">No threats found.</p>
                        <button
                            onClick={closeScanResults}
                            className="mt-4 px-4 py-2 bg-white border border-green-200 text-green-700 rounded-lg hover:bg-green-100 transition text-sm font-medium"
                        >
                            Close
                        </button>
                    </div>
                )}
            </div>
        )}

        {/* Dashboard Grid */}
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
      </>
      )}

      {activeTab === 'quarantine' && (
          <div className="space-y-6">
              {/* Quarantined Files */}
              <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                  <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                      <Archive size={20} className="text-red-500" /> Quarantined Files
                  </h3>
                  {quarantinedFiles.length === 0 ? (
                      <p className="text-gray-500 text-sm">No files in quarantine.</p>
                  ) : (
                      <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="p-3">File</th>
                                    <th className="p-3">Original Path</th>
                                    <th className="p-3">Date</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {quarantinedFiles.map((file) => (
                                    <tr key={file.id}>
                                        <td className="p-3 font-medium text-gray-800">{file.filename}</td>
                                        <td className="p-3 font-mono text-xs text-gray-500">{file.relative_path}</td>
                                        <td className="p-3 text-gray-500">{file.date}</td>
                                        <td className="p-3 flex justify-end gap-2">
                                            <button
                                                onClick={() => handleRestore(file.id)}
                                                className="text-indigo-600 hover:bg-indigo-50 px-2 py-1 rounded flex items-center gap-1 text-xs font-medium"
                                            >
                                                <RotateCcw size={14}/> Restore
                                            </button>
                                            <button
                                                onClick={() => handleDeleteQuarantine(file.id)}
                                                className="text-red-600 hover:bg-red-50 px-2 py-1 rounded flex items-center gap-1 text-xs font-medium"
                                            >
                                                <Trash2 size={14}/> Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                      </div>
                  )}
              </div>

              {/* Ignored Paths */}
              <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                  <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                      <EyeOff size={20} className="text-gray-500" /> Ignored Paths
                  </h3>
                   {ignoredPaths.length === 0 ? (
                      <p className="text-gray-500 text-sm">No paths ignored.</p>
                  ) : (
                      <ul className="divide-y divide-gray-100">
                          {ignoredPaths.map((path, idx) => (
                              <li key={idx} className="p-3 flex justify-between items-center hover:bg-gray-50">
                                  <span className="font-mono text-sm text-gray-600">{path}</span>
                                  <button
                                    onClick={() => handleUnIgnore(path)}
                                    className="text-red-600 hover:text-red-800 text-xs font-medium"
                                  >
                                      Remove (Scan Next Time)
                                  </button>
                              </li>
                          ))}
                      </ul>
                  )}
              </div>
          </div>
      )}
    </div>
  );
};

export default SecurityHub;
