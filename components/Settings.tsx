import React, { useState, useEffect } from 'react';
import { Save, Key, ShieldCheck, Zap, Download, FileCode, Check } from 'lucide-react';

const Settings: React.FC = () => {
  const [apiKey, setApiKey] = useState('');
  const [systemKeyPresent, setSystemKeyPresent] = useState(false);
  const [saved, setSaved] = useState(false);
  const [activeTab, setActiveTab] = useState<'general' | 'install'>('general');
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    const storedKey = localStorage.getItem('gemini_api_key');
    if (storedKey) setApiKey(storedKey);
    
    if (process.env.API_KEY) {
        setSystemKeyPresent(true);
    }
  }, []);

  const handleSave = () => {
    if (apiKey.trim()) {
        localStorage.setItem('gemini_api_key', apiKey.trim());
    } else {
        localStorage.removeItem('gemini_api_key');
    }
    setSaved(true);
    setTimeout(() => setSaved(false), 3000);
  };

  const phpCode = `<?php
/**
 * Plugin Name: WooSuite AI
 * Description: AI-powered SEO, Security, and Automation for WooCommerce.
 * Version: 1.0.0
 * Author: WooSuite AI Team
 */

if (!defined('ABSPATH')) exit;

function woosuite_ai_enqueue_scripts() {
    // Load the React Build
    wp_enqueue_script('woosuite-ai-app', plugins_url('/build/static/js/main.js', __FILE__), array('wp-element'), '1.0.0', true);
    wp_enqueue_style('woosuite-ai-styles', plugins_url('/build/static/css/main.css', __FILE__), array(), '1.0.0');
    
    // Pass Data from PHP to React
    wp_localize_script('woosuite-ai-app', 'woosuiteData', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'apiKey' => get_option('woosuite_gemini_key'),
    ));
}

function woosuite_ai_menu_page() {
    add_menu_page(
        'WooSuite AI', 
        'WooSuite AI', 
        'manage_options', 
        'woosuite-ai', 
        'woosuite_ai_render_app', 
        'dashicons-superhero', 
        3
    );
}

function woosuite_ai_render_app() {
    echo '<div id="root"></div>';
}

add_action('admin_enqueue_scripts', 'woosuite_ai_enqueue_scripts');
add_action('admin_menu', 'woosuite_ai_menu_page');
?>`;

  const copyToClipboard = () => {
    navigator.clipboard.writeText(phpCode);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-fade-in">
        <div className="flex justify-between items-center border-b border-gray-200 pb-6">
            <div>
                <h2 className="text-2xl font-bold text-gray-800">Plugin Settings</h2>
                <p className="text-gray-500">Configure your API connections and installation options.</p>
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
                        onClick={() => setActiveTab('install')}
                        className={`px-4 py-2 text-sm font-medium rounded-md transition ${activeTab === 'install' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-900'}`}
                    >
                        Installation
                    </button>
                </div>
            </div>
        </div>

        {activeTab === 'general' ? (
            <>
                <div className="flex justify-end">
                     <button 
                        onClick={handleSave}
                        className="bg-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-purple-700 transition flex items-center gap-2"
                    >
                        <Save size={18} /> {saved ? 'Saved!' : 'Save Changes'}
                    </button>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-purple-100 text-purple-600 rounded-lg">
                            <Key size={20} />
                        </div>
                        <div>
                            <h3 className="font-semibold text-gray-800">Gemini API Configuration</h3>
                            <p className="text-sm text-gray-500">Required for AI SEO, Search, and Email features.</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Google Gemini API Key</label>
                            <div className="relative">
                                <input 
                                    type="password" 
                                    value={apiKey}
                                    onChange={(e) => setApiKey(e.target.value)}
                                    placeholder="AIzaSy..."
                                    className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-200 focus:border-purple-500 outline-none transition font-mono"
                                />
                                <Key className="absolute left-3 top-3 text-gray-400" size={16} />
                            </div>
                            <p className="text-xs text-gray-500 mt-2">
                                Enter your personal API key from Google AI Studio. 
                                {systemKeyPresent && <span className="text-green-600 ml-1 font-medium flex items-center inline-flex gap-1"><ShieldCheck size={10} /> System key detected as fallback.</span>}
                            </p>
                        </div>

                        <div className="flex items-center gap-4 p-4 bg-blue-50 text-blue-800 rounded-lg text-sm border border-blue-100">
                            <Zap size={18} />
                            <span>Your API Key is stored securely and used for all AI requests.</span>
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
        ) : (
            <div className="space-y-6">
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-6">
                    <h3 className="text-lg font-bold text-amber-800 mb-2">Integration Instructions</h3>
                    <p className="text-amber-700 text-sm mb-4">
                        This application is currently in <strong>Demo Mode</strong> running in a standalone React environment. 
                        To install it on WordPress, follow these steps:
                    </p>
                    <ol className="list-decimal list-inside space-y-2 text-sm text-amber-800 font-medium">
                        <li>Run <code className="bg-amber-100 px-1 rounded">npm run build</code> to compile this React app.</li>
                        <li>Create a folder named <code className="bg-amber-100 px-1 rounded">woosuite-ai</code> in your WordPress <code className="bg-amber-100 px-1 rounded">wp-content/plugins</code> directory.</li>
                        <li>Create a file named <code className="bg-amber-100 px-1 rounded">woosuite-ai.php</code> inside that folder and paste the code below.</li>
                        <li>Copy the <code className="bg-amber-100 px-1 rounded">build</code> folder from this project into the plugin folder.</li>
                    </ol>
                </div>

                <div className="bg-gray-900 rounded-xl shadow-lg border border-gray-800 overflow-hidden">
                    <div className="bg-gray-800 px-4 py-2 border-b border-gray-700 flex justify-between items-center">
                        <span className="text-gray-300 text-xs font-mono flex items-center gap-2">
                            <FileCode size={14} /> woosuite-ai.php
                        </span>
                        <button 
                            onClick={copyToClipboard}
                            className="text-xs text-gray-300 hover:text-white flex items-center gap-1 transition"
                        >
                            {copied ? <Check size={14} /> : <Download size={14} />} {copied ? 'Copied' : 'Copy Code'}
                        </button>
                    </div>
                    <pre className="p-4 text-xs font-mono text-green-400 overflow-x-auto">
                        {phpCode}
                    </pre>
                </div>
            </div>
        )}
    </div>
  );
};

export default Settings;
