import React, { useState, useEffect } from 'react';
import { Save, Key, ShieldCheck, Zap, Download, FileCode, Check, Loader, AlertTriangle, CheckCircle } from 'lucide-react';

const Settings: React.FC = () => {
  const [apiKey, setApiKey] = useState('');
  const [systemKeyPresent, setSystemKeyPresent] = useState(false);

  // Save State
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  const [saveError, setSaveError] = useState('');

  // Connection Test State
  const [testingConnection, setTestingConnection] = useState(false);
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

  const [activeTab, setActiveTab] = useState<'general' | 'logs'>('general');
  const [copied, setCopied] = useState(false);

  // Logs State
  const [logs, setLogs] = useState<string[]>([]);
  const [loadingLogs, setLoadingLogs] = useState(false);

  useEffect(() => {
    // Check for global data first
    if (window.woosuiteData?.apiKey) {
      setApiKey(window.woosuiteData.apiKey);
      setSystemKeyPresent(true);
    } else {
        // Fallback to localStorage for dev/demo mode
        const storedKey = localStorage.getItem('groq_api_key');
        if (storedKey) setApiKey(storedKey);

        if (process.env.API_KEY) {
            setSystemKeyPresent(true);
        }
    }
  }, []);

  const handleSave = async () => {
    setSaveStatus('saving');
    setSaveError('');
    setTestResult(null); // Clear previous test result

    // Save locally (always useful for UI persistence)
    if (apiKey.trim()) {
        localStorage.setItem('groq_api_key', apiKey.trim());
    } else {
        localStorage.removeItem('groq_api_key');
    }

    // Save to WordPress Backend
    if (window.woosuiteData?.apiUrl) {
        try {
            const res = await fetch(`${window.woosuiteData.apiUrl}/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.woosuiteData.nonce
                },
                body: JSON.stringify({ apiKey: apiKey.trim() })
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({ message: res.statusText }));
                throw new Error(err.message || 'Server responded with an error');
            }

            // Update the global key
            window.woosuiteData.apiKey = apiKey.trim();
            setSaveStatus('success');
            setTimeout(() => setSaveStatus('idle'), 3000);
        } catch (e: any) {
            console.error('Failed to save settings to WordPress', e);
            setSaveStatus('error');
            setSaveError(e.message || 'Failed to save to database. Check console.');
        }
    } else {
        // Demo Mode (No backend)
        setSaveStatus('success');
        setTimeout(() => setSaveStatus('idle'), 3000);
    }
  };

  const fetchLogs = async () => {
    if (!window.woosuiteData?.apiUrl) return;
    setLoadingLogs(true);
    try {
        const res = await fetch(`${window.woosuiteData.apiUrl}/system-logs`, {
            headers: { 'X-WP-Nonce': window.woosuiteData.nonce }
        });
        const data = await res.json();
        if (data.logs) setLogs(data.logs);
    } catch (e) {
        console.error('Failed to fetch logs', e);
    } finally {
        setLoadingLogs(false);
    }
  };

  useEffect(() => {
      if (activeTab === 'logs') {
          fetchLogs();
          const interval = setInterval(fetchLogs, 5000); // Auto-refresh logs
          return () => clearInterval(interval);
      }
  }, [activeTab]);

  const handleTestConnection = async () => {
      if (!window.woosuiteData?.apiUrl) {
          setTestResult({ success: false, message: 'Cannot test in Demo Mode (No API URL).' });
          return;
      }

      setTestingConnection(true);
      setTestResult(null);

      try {
          const res = await fetch(`${window.woosuiteData.apiUrl}/settings/test-connection`, {
              method: 'POST',
              headers: {
                  'X-WP-Nonce': window.woosuiteData.nonce
              }
          });

          const data = await res.json();

          if (res.ok && data.success) {
              setTestResult({ success: true, message: 'Connection Successful! Groq API is reachable.' });
          } else {
              setTestResult({
                  success: false,
                  message: data.message || 'Connection Failed: Unknown Error'
              });
          }
      } catch (e: any) {
          setTestResult({ success: false, message: 'Network Error: ' + e.message });
      } finally {
          setTestingConnection(false);
      }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-fade-in">
        <div className="flex justify-between items-center border-b border-gray-200 pb-6">
            <div>
                <h2 className="text-2xl font-bold text-gray-800">Plugin Settings</h2>
                <p className="text-gray-500">Configure your API connections and logs.</p>
            </div>
            <div className="flex gap-3">
                 <div className="flex bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                    <button 
                        onClick={() => setActiveTab('general')}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition ${activeTab === 'general' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-900'}`}
                    >
                        General
                    </button>
                     <button
                        onClick={() => setActiveTab('logs')}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition ${activeTab === 'logs' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-900'}`}
                    >
                        System Logs
                    </button>
                </div>
            </div>
        </div>

        {activeTab === 'logs' ? (
            <div className="bg-gray-900 text-gray-300 rounded-xl shadow-lg border border-gray-800 overflow-hidden font-mono text-xs">
                <div className="flex justify-between items-center p-4 bg-gray-800 border-b border-gray-700">
                    <span className="font-bold text-gray-100 flex items-center gap-2">
                        <FileCode size={16} /> System Logs (Last 50 Entries)
                    </span>
                    <button onClick={fetchLogs} disabled={loadingLogs} className="text-gray-400 hover:text-white transition">
                        {loadingLogs ? <Loader className="animate-spin" size={14} /> : <Zap size={14} />} Refresh
                    </button>
                </div>
                <div className="p-4 h-96 overflow-y-auto space-y-1">
                    {logs.length === 0 ? (
                        <div className="text-gray-500 italic">No logs found. Run a process to see activity.</div>
                    ) : (
                        logs.map((log, i) => (
                            <div key={i} className="border-b border-gray-800 pb-1 last:border-0">{log}</div>
                        ))
                    )}
                </div>
            </div>
        ) : activeTab === 'general' ? (
            <>
                <div className="flex justify-end gap-2 items-center">
                     {saveStatus === 'error' && (
                         <span className="text-red-600 text-sm font-medium flex items-center gap-1">
                             <AlertTriangle size={16} /> {saveError || 'Save Failed'}
                         </span>
                     )}
                     <button 
                        onClick={handleSave}
                        disabled={saveStatus === 'saving'}
                        className={`px-6 py-2 rounded-lg font-medium transition flex items-center gap-2 text-white
                            ${saveStatus === 'success' ? 'bg-green-600 hover:bg-green-700' :
                              saveStatus === 'error' ? 'bg-red-600 hover:bg-red-700' :
                              'bg-purple-600 hover:bg-purple-700'}`}
                    >
                        {saveStatus === 'saving' ? <Loader className="animate-spin" size={18} /> :
                         saveStatus === 'success' ? <Check size={18} /> :
                         <Save size={18} />}
                        {saveStatus === 'saving' ? 'Saving...' :
                         saveStatus === 'success' ? 'Saved to DB!' :
                         'Save Changes'}
                    </button>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-purple-100 text-purple-600 rounded-lg">
                            <Key size={20} />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-800">Groq API Configuration</h3>
                            <p className="text-sm text-gray-500">Powered by Llama 3.1 & 3.2 Vision (High Speed).</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Groq API Key</label>
                            <div className="relative">
                                <input 
                                    type="password" 
                                    value={apiKey}
                                    onChange={(e) => setApiKey(e.target.value)}
                                    placeholder="gsk_..."
                                    className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-200 focus:border-purple-500 outline-none transition font-mono"
                                />
                                <Key className="absolute left-3 top-3 text-gray-400" size={16} />
                            </div>
                            <div className="flex justify-between items-start mt-2">
                                <p className="text-xs text-gray-500">
                                    Enter your Groq API Key.
                                    {systemKeyPresent && <span className="text-green-600 ml-1 font-medium flex items-center inline-flex gap-1"><ShieldCheck size={10} /> System key detected.</span>}
                                </p>
                                <button
                                    onClick={handleTestConnection}
                                    disabled={testingConnection || !apiKey}
                                    className="text-sm text-purple-600 font-medium hover:text-purple-800 hover:underline flex items-center gap-1 disabled:opacity-50"
                                >
                                    {testingConnection ? <Loader className="animate-spin" size={14} /> : <Zap size={14} />}
                                    {testingConnection ? 'Testing...' : 'Test Connection'}
                                </button>
                            </div>
                        </div>

                        {/* Test Result Feedback */}
                        {testResult && (
                            <div className={`p-4 rounded-lg text-sm border flex items-start gap-3 ${testResult.success ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'}`}>
                                {testResult.success ? <CheckCircle size={18} className="mt-0.5" /> : <AlertTriangle size={18} className="mt-0.5" />}
                                <div>
                                    <div className="font-bold">{testResult.success ? 'Success' : 'Connection Failed'}</div>
                                    <div>{testResult.message}</div>
                                </div>
                            </div>
                        )}

                        <div className="flex items-center gap-4 p-4 bg-blue-50 text-blue-800 rounded-lg text-sm border border-blue-100">
                            <Zap size={18} />
                            <span>Your API Key is stored securely in the database and used for all AI requests.</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="font-semibold text-gray-800 mb-4">General Preferences</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-700">Auto-update Plugin</span>
                                <input type="checkbox" className="toggle-checkbox accent-purple-600" defaultChecked />
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-700">Email Notifications</span>
                                <input type="checkbox" className="toggle-checkbox accent-purple-600" defaultChecked />
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-700">Beta Features</span>
                                <input type="checkbox" className="toggle-checkbox accent-purple-600" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 className="font-semibold text-gray-800 mb-4">Maintenance</h3>
                        <div className="space-y-3">
                            <button className="w-full py-2 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition text-left flex justify-between items-center">
                                Clear System Cache <span className="text-xs text-gray-400">128 MB</span>
                            </button>
                            <button className="w-full py-2 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition text-left">
                                Reset Onboarding Tour
                            </button>
                        </div>
                    </div>
                </div>
            </>
        ) : null}
    </div>
  );
};

export default Settings;
