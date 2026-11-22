import { Suspense } from "react";
import { AdminPanelShell } from "./AdminPanelShell";
import { Spinner } from "@/components/ui/Spinner";

export default function AdminPanelLayout({ children }: { children: React.ReactNode }) {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center">
          <Spinner className="h-8 w-8" />
        </div>
      }
    >
      <AdminPanelShell>{children}</AdminPanelShell>
    </Suspense>
  );
}