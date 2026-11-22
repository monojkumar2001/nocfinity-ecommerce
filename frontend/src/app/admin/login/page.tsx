"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { loginStaff, verifyStaffTwoFactor, isStaff } from "@/lib/auth";
import { AuthCard } from "@/components/auth/AuthCard";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

function StaffLoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/admin";
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [code, setCode] = useState("");
  const [challengeToken, setChallengeToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function finishLogin(user: { is_staff?: boolean; roles?: string[]; permissions?: string[] }) {
    if (!isStaff(user)) {
      setError("Staff access only.");
      return;
    }
    router.push(next);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      if (challengeToken) {
        const res = await verifyStaffTwoFactor({ challenge_token: challengeToken, code });
        await finishLogin(res.user);
        return;
      }

      const res = await loginStaff({ email, password });
      if (res.requires_two_factor && res.challenge_token) {
        setChallengeToken(res.challenge_token);
        return;
      }
      await finishLogin(res.user);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Login failed");
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthCard title="Staff portal" subtitle="Separate admin auth guard · AURA">
      <form onSubmit={handleSubmit} className="space-y-4">
        {!challengeToken ? (
          <>
            <Input label="Work email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
            <Input
              label="Password"
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </>
        ) : (
          <Input
            label="Authenticator code"
            required
            value={code}
            onChange={(e) => setCode(e.target.value)}
            placeholder="6-digit TOTP or recovery code"
            autoFocus
          />
        )}
        {error ? <p className="text-sm text-red-600">{error}</p> : null}
        <Button type="submit" className="w-full" disabled={loading}>
          {loading ? "Signing in…" : challengeToken ? "Verify & continue" : "Sign in to admin"}
        </Button>
      </form>
      <p className="mt-4 text-center text-sm text-muted-foreground">
        <Link href="/" className="text-accent hover:underline">
          Back to storefront
        </Link>
      </p>
    </AuthCard>
  );
}

export default function AdminLoginPage() {
  return (
    <Suspense fallback={<Spinner className="mx-auto mt-24" />}>
      <StaffLoginForm />
    </Suspense>
  );
}
