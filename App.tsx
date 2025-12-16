import React, { useState, useEffect } from 'react';
import { ViewState, Product, Order } from './types';
import Dashboard from './components/Dashboard';
import SeoManager from './components/SeoManager';
import SecurityHub from './components/SecurityHub';
import AiSearch from './components/AiSearch';
import OrderManager from './components/OrderManager';
import BackupManager from './components/BackupManager';
import EmailAutomation from './components/EmailAutomation';
import Settings from './components/Settings';
import { LayoutDashboard, Search, Shield, ShoppingBag, Database, Box, Mail, Settings as SettingsIcon, Beaker, Menu, X } from 'lucide-react';

// Initial Mock Data
const initialProducts: Product[] = [];
const initialOrders: Order[] = [];

const App: React.FC = () => {
  const [view, setView] = useState<ViewState>('dashboard');
  const [products, setProducts] = useState<Product[]>(initialProducts);
  const [orders, setOrders] = useState<Order[]>(initialOrders);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  useEffect(() => {
    const fetchRealData = async () => {
        if (!window.woosuiteData?.apiUrl) return;

        try {
            // Fetch Products
            const prodRes = await fetch(`${window.woosuiteData.apiUrl}/products`, {
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
      }`}
    >
      <Icon size={20} />
      <span className="font-medium">{label}</span>
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
        fixed inset-y-0 left-0 z-50 w-64 bg-gray-50 border-r border-gray-200 flex flex-col transition-transform duration-300 ease-in-out
        md:relative md:translate-x-0
        ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'}
      `}>
        <div className="p-6 flex items-center justify-between">
           <div className="flex items-center gap-2 text-purple-700 font-bold text-xl">
              <Box className="fill-current" />
              <span>WooSuite AI</span>
           </div>
           {/* Mobile Close Button */}
           <button
             onClick={() => setIsMobileMenuOpen(false)}
             className="md:hidden text-gray-500 hover:text-gray-700"
           >
             <X size={24} />
           </button>
        </div>


        <nav className="flex-1 px-4 space-y-2 overflow-y-auto">
          <NavItem id="dashboard" label="Dashboard" icon={LayoutDashboard} />
          
          <div className="pt-4 pb-2 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Features</div>
          <NavItem id="seo" label="AI SEO (GEO)" icon={Search} />
          <NavItem id="security" label="Security & Firewall" icon={Shield} />
          <NavItem id="email" label="Email & Marketing" icon={Mail} />
          <NavItem id="orders" label="Order Manager" icon={ShoppingBag} />
          <NavItem id="search" label="AI Search Config" icon={Search} />
          <NavItem id="backups" label="Cloud Backups" icon={Database} />
          
          <div className="pt-4 pb-2 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</div>
          <NavItem id="settings" label="Settings" icon={SettingsIcon} />
        </nav>

        <div className="p-4 border-t border-gray-200">
            <div className="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                <p className="text-xs font-medium text-gray-500">API Usage</p>
                <div className="w-full bg-gray-100 h-1.5 rounded-full mt-2 mb-1">
                    <div className="bg-green-500 h-1.5 rounded-full w-[45%]"></div>
                </div>
                <p className="text-xs text-right text-gray-400">450 / 1000 credits</p>
            </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col min-h-screen">
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
                {view === 'seo' && <SeoManager products={products} onUpdateProduct={handleUpdateProduct} />}
                {view === 'security' && <SecurityHub />}
                {view === 'search' && <AiSearch products={products} />}
                {view === 'orders' && <OrderManager orders={orders} />}
                {view === 'email' && <EmailAutomation />}
                {view === 'backups' && <BackupManager />}
                {view === 'settings' && <Settings />}
            </div>
        </div>
      </main>
    </div>
  );
};

export default App;
