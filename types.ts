export type ViewState = 'dashboard' | 'seo' | 'security' | 'search' | 'orders' | 'email' | 'backups' | 'settings';

export type ContentType = 'product' | 'post' | 'page' | 'image';

export interface ContentItem {
  id: number;
  name: string;
  description: string;
  type: ContentType;
  permalink?: string;
  metaTitle?: string;
  metaDescription?: string;
  llmSummary?: string;

  // Type specific
  price?: number;
  imageUrl?: string;
  altText?: string;
}

// Alias for backward compatibility
export type Product = ContentItem;

export interface Order {
  id: number;
  customer: string;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
  total: number;
  date: string;
  customerNote?: string;
}

export interface SecurityLog {
  id: number;
  ip_address: string;
  event: string;
  created_at: string;
  severity: 'low' | 'medium' | 'high';
  blocked: boolean | number; // DB returns 0/1 (number)
}

export interface Backup {
  id: number;
  name: string;
  size: string;
  date: string;
  location: 'local' | 'drive';
}

export interface SeoGenerationResult {
  title: string;
  description: string;
  llmSummary: string;
}

export interface EmailRule {
  id: string;
  name: string;
  trigger: 'order_completed' | 'order_cancelled' | 'abandoned_cart' | 'new_customer';
  subject: string;
  body: string;
  active: boolean;
}

declare global {
  interface Window {
    woosuiteData: {
      root: string;
      homeUrl?: string;
      nonce: string;
      apiUrl: string;
      apiKey: string;
    };
  }
}
