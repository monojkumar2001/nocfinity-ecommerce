export type Category = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  is_active: boolean;
  parent_id?: number | null;
  children?: Category[];
  product_count?: number;
};

export type ProductImage = {
  id: number;
  url: string;
  alt?: string | null;
  is_primary: boolean;
};

export type ProductCategoryRef = {
  id: number;
  name: string;
  slug: string;
};

export type Product = {
  id: number;
  name: string;
  slug: string;
  sku: string;
  price: number;
  compare_at_price: number | null;
  stock: number;
  in_stock: boolean;
  is_active: boolean;
  color?: string | null;
  size?: string | null;
  dress_style?: string | null;
  category: ProductCategoryRef | null;
  thumbnail: string | null;
  images: ProductImage[];
  description?: string | null;
  average_rating?: number | null;
  reviews_count?: number;
};

export type ProductDetailPayload = {
  product: Product;
  related: Product[];
};

export type CartItem = {
  id: number;
  product_id: number;
  name: string;
  slug: string;
  sku: string;
  price: number;
  compare_at_price: number | null;
  quantity: number;
  line_total: number;
  stock: number;
  color?: string | null;
  size?: string | null;
  image: string | null;
};

export type CartCoupon = {
  code: string;
  type: "percentage" | "fixed";
  value: number;
};

export type Cart = {
  id: number;
  session_id: string | null;
  items: CartItem[];
  coupon: CartCoupon | null;
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
};

export type OrderItem = {
  product_name: string;
  product_sku: string;
  price: number;
  quantity: number;
  total: number;
};

export type ShippingAddress = {
  name: string;
  phone: string;
  line1: string;
  line2?: string | null;
  city: string;
  state: string;
  postal_code: string;
  country: string;
};

export type OrderCustomer = {
  id?: number;
  name?: string | null;
  email?: string | null;
};

export type OrderStatusStep = {
  key: string;
  label: string;
  state: "done" | "current" | "upcoming" | "cancelled";
};

export type Order = {
  id: number;
  order_number: string;
  status: string;
  payment_status: string;
  total_amount: number;
  item_count?: number;
  created_at?: string;
  subtotal?: number;
  discount_amount?: number;
  shipping_amount?: number;
  shipping_address?: ShippingAddress;
  customer_note?: string | null;
  customer?: string | OrderCustomer | null;
  allowed_transitions?: string[];
  items?: OrderItem[];
  status_timeline?: OrderStatusStep[];
};

export type User = {
  id: number;
  name: string;
  email: string;
  roles?: string[];
  permissions?: string[];
  is_staff?: boolean;
  two_factor_enabled?: boolean;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type Coupon = {
  id: number;
  code: string;
  type: "percentage" | "fixed";
  value: number;
  minimum_order_amount: number;
  maximum_discount: number | null;
  usage_limit: number | null;
  used_count: number;
  starts_at: string | null;
  expires_at: string | null;
  is_active: boolean;
};

export type DashboardStats = {
  todays_orders: number;
  todays_revenue: number;
  total_customers: number;
  low_stock_count: number;
};

export type SalesChartPoint = {
  day: string;
  revenue: number;
  orders: number;
};

export type DashboardData = {
  stats: DashboardStats;
  sales_chart: SalesChartPoint[];
  recent_orders: Order[];
};

export type RoleRecord = {
  id: number;
  name: string;
  permissions: string[];
  is_super?: boolean;
  users_count?: number;
};

export type StaffMember = {
  id: number;
  name: string;
  email: string;
  roles: string[];
  permissions?: string[];
  is_super?: boolean;
};