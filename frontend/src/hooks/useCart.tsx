"use client";

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { apiRequest } from "@/lib/api";
import type { Cart } from "@/lib/types";

type CartContextValue = {
  cart: Cart | null;
  loading: boolean;
  refreshCart: () => Promise<void>;
  addToCart: (productId: number, quantity?: number) => Promise<void>;
  updateQuantity: (itemId: number, quantity: number) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => Promise<void>;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: ReactNode }) {
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(false);

  const refreshCart = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await apiRequest<Cart>("/cart");
      setCart(data);
    } finally {
      setLoading(false);
    }
  }, []);

  const addToCart = useCallback(
    async (productId: number, quantity = 1) => {
      const { data } = await apiRequest<Cart>("/cart/items", {
        method: "POST",
        body: JSON.stringify({ product_id: productId, quantity }),
      });
      setCart(data);
    },
    [],
  );

  const updateQuantity = useCallback(async (itemId: number, quantity: number) => {
    const { data } = await apiRequest<Cart>(`/cart/items/${itemId}`, {
      method: "PUT",
      body: JSON.stringify({ quantity }),
    });
    setCart(data);
  }, []);

  const removeItem = useCallback(async (itemId: number) => {
    const { data } = await apiRequest<Cart>(`/cart/items/${itemId}`, {
      method: "DELETE",
    });
    setCart(data);
  }, []);

  const applyCoupon = useCallback(async (code: string) => {
    const { data } = await apiRequest<Cart>("/cart/coupon", {
      method: "POST",
      body: JSON.stringify({ code }),
    });
    setCart(data);
  }, []);

  const removeCoupon = useCallback(async () => {
    const { data } = await apiRequest<Cart>("/cart/coupon", {
      method: "DELETE",
    });
    setCart(data);
  }, []);

  const value = useMemo(
    () => ({
      cart,
      loading,
      refreshCart,
      addToCart,
      updateQuantity,
      removeItem,
      applyCoupon,
      removeCoupon,
    }),
    [
      cart,
      loading,
      refreshCart,
      addToCart,
      updateQuantity,
      removeItem,
      applyCoupon,
      removeCoupon,
    ],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) {
    throw new Error("useCart must be used within CartProvider");
  }
  return ctx;
}