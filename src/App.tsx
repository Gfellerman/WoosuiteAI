import React, { useState, useEffect } from 'react';
import { ViewState, Product, Order } from './types';
import Dashboard from './components/Dashboard';
import SeoManager from './components/SeoManager';
import ContentEnhancer from './components/ContentEnhancer';
import SecurityHub from './components/SecurityHub';
import BackupManager from './components/BackupManager';
import Settings from './components/Settings';
import { LayoutDashboard, Search, Shield, ShoppingBag, Database, Box, Mail, Settings as SettingsIcon, Beaker, Menu, X, PenTool, ChevronLeft, ChevronRight } from 'lucide-react';

// Initial Mock Data
const initialProducts: Product[] = [];
const initialOrders: Order[] = [];

const App: React.FC = () => {
  const [view, setView] = useState<ViewState>('dashboard');
  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isCollapsed, setIsCollapsed] = useState(false);

  useEffect(() => {
    const fetchRealData = async () => {
        if (!window.woosuiteData?.apiUrl) return;

        try {
            // Fetch Products
            const prodRes = await fetch(`${window.woosuiteData.apiUrl}/content?type=product`, {
                headers: { 'X-WP-Nonce': window.woosuiteData.nonce }
            });
            if (prodRes.ok) {
                const fetchedProducts: Product[] = await prodRes.json();
                if (fetchedProducts && fetchedProducts.length > 0) {
                    setProducts(fetchedProducts);
                }
            }
        } catch (e) {
            console.error("Failed to fetch real data", e);
        }
    };

    fetchRealData();
  }, []);

  const handleUpdateProduct = (updated: Product) => {
    setProducts(prev => prev.map(p => p.id === updated.id ? updated : p));
  };

  const NavItem = ({ id, label, icon: Icon }: { id: ViewState, label: string, icon: React.ElementType }) => (
    <button
      onClick={() => {
        setView(id);
        setIsMobileMenuOpen(false);
      }}
      className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors ${
        view === id 
          ? 'bg-purple-600 text-white shadow-md' 
          : 'text-gray-600 hover:bg-white hover:text-purple-600'
      } ${isCollapsed ? 'justify-center' : ''}`}
      title={isCollapsed ? label : ''}
    >
      <Icon size={20} className="shrink-0" />
      {!isCollapsed && <span className="font-medium whitespace-nowrap">{label}</span>}
    </button>
  );

  return (
    <div className="flex min-h-screen bg-gray-100 text-gray-800 font-sans relative">

      {/* Mobile Sidebar Overlay */}
      {isMobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 md:hidden"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={`
        fixed inset-y-0 left-0 z-50 bg-gray-50 border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out h-full
        md:fixed md:inset-y-0 md:left-0 md:translate-x-0
        ${isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full'}
        ${!isMobileMenuOpen && (isCollapsed ? 'md:w-20' : 'md:w-64')}
      `}>
        <div className={`p-6 flex items-center ${isCollapsed ? 'justify-center' : 'justify-between'}`}>
           <div className="flex items-center gap-2 text-purple-700 font-bold text-xl overflow-hidden">
              <Box className="fill-current shrink-0" />
              {!isCollapsed && <span>WooSuite AI</span>}
           </div>
           {/* Mobile Close Button */}
           <button
             onClick={() => setIsMobileMenuOpen(false)}
             className="md:hidden text-gray-500 hover:text-gray-700"
           >
             <X size={24} />
           </button>
        </div>


        <nav className="flex-1 px-4 space-y-2 overflow-y-auto overflow-x-hidden">
          <NavItem id="dashboard" label="Dashboard" icon={LayoutDashboard} />
          
          {!isCollapsed && <div className="pt-4 pb-2 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Features</div>}
          {isCollapsed && <div className="h-4"></div>}
          <NavItem id="seo" label="AI SEO (GEO)" icon={Search} />
          <NavItem id="content-enhancer" label="Content Enhancer" icon={PenTool} />
          <NavItem id="security" label="Security & Firewall" icon={Shield} />
          <NavItem id="backups" label="Cloud Backups" icon={Database} />
          
          {!isCollapsed && <div className="pt-4 pb-2 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</div>}
          {isCollapsed && <div className="h-4"></div>}
          <NavItem id="settings" label="Settings" icon={SettingsIcon} />
        </nav>

        {/* Collapse Toggle (Desktop) */}
        <div className="hidden md:flex p-4 border-t border-gray-200 justify-end">
            <button
                onClick={() => setIsCollapsed(!isCollapsed)}
                className="p-2 text-gray-500 hover:text-purple-600 hover:bg-gray-100 rounded transition"
            >
                {isCollapsed ? <ChevronRight size={20} /> : <ChevronLeft size={20} />}
            </button>
        </div>

      </aside>

      {/* Main Content */}
      <main className={`flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out
          ${isCollapsed ? 'md:ml-20' : 'md:ml-64'}
      `}>
        {/* Mobile Header */}
        <header className="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 md:hidden sticky top-0 z-30">
             <div className="font-bold text-gray-800">WooSuite AI</div>
             <button
               onClick={() => setIsMobileMenuOpen(true)}
               className="p-2 text-gray-600 hover:bg-gray-100 rounded-md"
               aria-label="Toggle menu"
             >
               <Menu />
             </button>
        </header>

        {/* Header - Desktop */}
        <header className="hidden md:flex bg-white border-b border-gray-200 h-16 items-center justify-between px-8 sticky top-0 z-30">
            <h1 className="text-xl font-semibold text-gray-800 capitalize">{view.replace('-', ' ')}</h1>
            <div className="flex items-center gap-4">
                <span className="text-sm text-gray-500">Welcome, Admin</span>
                <div className="w-8 h-8 rounded-full bg-purple-100 border border-purple-200 flex items-center justify-center text-purple-700 font-bold text-xs">
                    AD
                </div>
            </div>
        </header>

        {/* Content Area */}
        <div className="flex-1 p-4 md:p-8">
            <div className="max-w-6xl mx-auto">
                {view === 'dashboard' && <Dashboard />}
                {view === 'seo' && <SeoManager />}
                {view === 'content-enhancer' && <ContentEnhancer />}
                {view === 'security' && <SecurityHub />}
                {view === 'backups' && <BackupManager />}
                {view === 'settings' && <Settings />}
            </div>
        </div>
      </main>
    </div>
  );
};

export default App;
