import React from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { ShieldCheck, Search, ShoppingBag, HardDrive } from 'lucide-react';

const trafficData = [
  { name: 'Mon', visits: 4000, blocked: 240 },
  { name: 'Tue', visits: 3000, blocked: 139 },
  { name: 'Wed', visits: 2000, blocked: 980 },
  { name: 'Thu', visits: 2780, blocked: 390 },
  { name: 'Fri', visits: 1890, blocked: 480 },
  { name: 'Sat', visits: 2390, blocked: 380 },
  { name: 'Sun', visits: 3490, blocked: 430 },
];

const seoData = [
  { name: 'Optimized', value: 85 },
  { name: 'Missing', value: 15 },
];

const Dashboard: React.FC = () => {
  return (
    <div className="space-y-6 animate-fade-in">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 bg-purple-100 text-purple-600 rounded-full">
            <ShieldCheck size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500">Threats Blocked</p>
            <h3 className="text-2xl font-bold text-gray-800">3,291</h3>
          </div>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 bg-blue-100 text-blue-600 rounded-full">
            <Search size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500">AI Searches</p>
            <h3 className="text-2xl font-bold text-gray-800">14.5k</h3>
          </div>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 bg-green-100 text-green-600 rounded-full">
            <ShoppingBag size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500">Orders Processed</p>
            <h3 className="text-2xl font-bold text-gray-800">1,203</h3>
          </div>
        </div>
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 bg-amber-100 text-amber-600 rounded-full">
            <HardDrive size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500">Last Backup</p>
            <h3 className="text-xl font-bold text-gray-800">2h ago</h3>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-lg font-semibold mb-4 text-gray-800">Traffic vs Threats</h2>
          <div className="h-72 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={trafficData}>
                <defs>
                  <linearGradient id="colorVisits" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#8884d8" stopOpacity={0.8}/>
                    <stop offset="95%" stopColor="#8884d8" stopOpacity={0}/>
                  </linearGradient>
                  <linearGradient id="colorBlocked" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#ef4444" stopOpacity={0.8}/>
                    <stop offset="95%" stopColor="#ef4444" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="name" stroke="#94a3b8" />
                <YAxis stroke="#94a3b8" />
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                <Tooltip />
                <Area type="monotone" dataKey="visits" stroke="#8884d8" fillOpacity={1} fill="url(#colorVisits)" />
                <Area type="monotone" dataKey="blocked" stroke="#ef4444" fillOpacity={1} fill="url(#colorBlocked)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <h2 className="text-lg font-semibold mb-4 text-gray-800">SEO Health</h2>
          <div className="h-72 w-full flex flex-col justify-center items-center">
             <ResponsiveContainer width="100%" height={200}>
              <BarChart data={seoData}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="value" fill="#10b981" radius={[4, 4, 0, 0]} barSize={40} />
              </BarChart>
            </ResponsiveContainer>
            <div className="mt-4 text-center">
              <p className="text-3xl font-bold text-gray-800">92/100</p>
              <p className="text-sm text-gray-500">Overall Score</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
