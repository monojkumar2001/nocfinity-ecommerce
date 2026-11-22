"use client";

import { useParams } from "next/navigation";
import { ProductForm } from "@/components/admin/ProductForm";

export default function EditProductPage() {
  const { id } = useParams<{ id: string }>();
  return (
    <div>
      <h1 className="font-display text-2xl font-bold">Edit product</h1>
      <div className="mt-6">
        <ProductForm productId={Number(id)} />
      </div>
    </div>
  );
}