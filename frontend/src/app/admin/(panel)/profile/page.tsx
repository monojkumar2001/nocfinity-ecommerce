"use client";

import { useEffect, useState } from "react";
import { apiRequest } from "@/lib/api";
import { fetchCurrentUser } from "@/lib/auth";
import type { User } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { PASSWORD_HINT, validateOptionalPassword } from "@/lib/validation";

type TwoFactorSetup = {
  secret: string;
  otpauth_url: string;
  qr_svg: string;
};

export default function AdminProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [name, setName] = useState("");
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [password_confirmation, setConfirm] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
  const [setup, setSetup] = useState<TwoFactorSetup | null>(null);
  const [totpCode, setTotpCode] = useState("");
  const [disablePassword, setDisablePassword] = useState("");
  const [disableCode, setDisableCode] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
  const [twoFactorMessage, setTwoFactorMessage] = useState<string | null>(null);
  const [twoFactorError, setTwoFactorError] = useState<string | null>(null);

  useEffect(() => {
    apiRequest<User>("/admin/me").then(({ data }) => {
      setUser(data);
      setName(data.name);
      setTwoFactorEnabled(Boolean(data.two_factor_enabled));
    });
  }, []);

  async function saveProfile(e: React.FormEvent) {
    e.preventDefault();
    setMessage(null);
    setError(null);

    if (!name.trim()) {
      setError("Display name is required.");
      return;
    }
    if (password && !currentPassword) {
      setError("Current password is required to set a new password.");
      return;
    }
    const passwordError = validateOptionalPassword(password, password_confirmation);
    if (passwordError) {
      setError(passwordError);
      return;
    }

    const body: Record<string, string> = { name };
    if (password) {
      body.current_password = currentPassword;
      body.password = password;
      body.password_confirmation = password_confirmation;
    }
    try {
      await apiRequest("/admin/profile", { method: "PUT", body: JSON.stringify(body) });
      await fetchCurrentUser();
      setMessage("Profile updated");
      setPassword("");
      setConfirm("");
      setCurrentPassword("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Update failed");
    }
  }

  async function startTwoFactor() {
    setTwoFactorError(null);
    setTwoFactorMessage(null);
    setRecoveryCodes(null);
    try {
      const { data } = await apiRequest<TwoFactorSetup>("/admin/2fa/setup", { method: "POST" });
      setSetup(data);
    } catch (err) {
      setTwoFactorError(err instanceof Error ? err.message : "Could not start 2FA setup");
    }
  }

  async function confirmTwoFactor(e: React.FormEvent) {
    e.preventDefault();
    setTwoFactorError(null);
    try {
      const { data } = await apiRequest<{ enabled: boolean; recovery_codes: string[] }>(
        "/admin/2fa/confirm",
        { method: "POST", body: JSON.stringify({ code: totpCode }) },
      );
      setTwoFactorEnabled(true);
      setSetup(null);
      setTotpCode("");
      setRecoveryCodes(data.recovery_codes);
      setTwoFactorMessage("Two-factor authentication enabled.");
    } catch (err) {
      setTwoFactorError(err instanceof Error ? err.message : "Invalid code");
    }
  }

  async function disableTwoFactor(e: React.FormEvent) {
    e.preventDefault();
    setTwoFactorError(null);
    try {
      await apiRequest("/admin/2fa", {
        method: "DELETE",
        body: JSON.stringify({ password: disablePassword, code: disableCode }),
      });
      setTwoFactorEnabled(false);
      setDisablePassword("");
      setDisableCode("");
      setRecoveryCodes(null);
      setTwoFactorMessage("Two-factor authentication disabled.");
    } catch (err) {
      setTwoFactorError(err instanceof Error ? err.message : "Could not disable 2FA");
    }
  }

  if (!user) return <Spinner className="mt-12" />;

  return (
    <div className="max-w-xl space-y-8">
      <div>
        <h1 className="font-display text-2xl font-bold">Profile</h1>
        <p className="text-sm text-muted-foreground">{user.email}</p>
      </div>

      <form onSubmit={saveProfile} className="space-y-4 rounded-xl border border-border bg-white p-6">
        <Input label="Display name" required value={name} onChange={(e) => setName(e.target.value)} />
        <Input
          label="Current password"
          type="password"
          value={currentPassword}
          onChange={(e) => setCurrentPassword(e.target.value)}
        />
        <Input
          label="New password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Leave blank to keep current"
        />
        <Input
          label="Confirm password"
          type="password"
          value={password_confirmation}
          onChange={(e) => setConfirm(e.target.value)}
        />
        {password ? <p className="text-xs text-muted-foreground">{PASSWORD_HINT}</p> : null}
        {message ? <p className="text-sm text-green-700">{message}</p> : null}
        {error ? <p className="text-sm text-red-600">{error}</p> : null}
        <Button type="submit">Save changes</Button>
      </form>

      <section className="space-y-4 rounded-xl border border-border bg-white p-6">
        <div>
          <h2 className="font-display text-lg font-semibold">Two-factor authentication (TOTP)</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Status: {twoFactorEnabled ? "Enabled" : "Disabled"}
          </p>
        </div>

        {!twoFactorEnabled && !setup ? (
          <Button type="button" onClick={startTwoFactor}>
            Enable authenticator app
          </Button>
        ) : null}

        {setup ? (
          <form onSubmit={confirmTwoFactor} className="space-y-4">
            <div
              className="inline-block rounded-lg border border-border bg-white p-3"
              dangerouslySetInnerHTML={{ __html: setup.qr_svg }}
            />
            <p className="text-xs text-muted-foreground break-all">Secret: {setup.secret}</p>
            <Input
              label="Enter 6-digit code"
              required
              value={totpCode}
              onChange={(e) => setTotpCode(e.target.value)}
            />
            <Button type="submit">Confirm & enable</Button>
          </form>
        ) : null}

        {twoFactorEnabled ? (
          <form onSubmit={disableTwoFactor} className="space-y-4">
            <Input
              label="Current password"
              type="password"
              required
              value={disablePassword}
              onChange={(e) => setDisablePassword(e.target.value)}
            />
            <Input
              label="Authenticator or recovery code"
              required
              value={disableCode}
              onChange={(e) => setDisableCode(e.target.value)}
            />
            <Button type="submit" variant="outline">
              Disable 2FA
            </Button>
          </form>
        ) : null}

        {recoveryCodes ? (
          <div className="rounded-lg bg-surface p-4 text-sm">
            <p className="font-medium">Save these recovery codes now:</p>
            <ul className="mt-2 grid grid-cols-2 gap-1 font-mono text-xs">
              {recoveryCodes.map((code) => (
                <li key={code}>{code}</li>
              ))}
            </ul>
          </div>
        ) : null}

        {twoFactorMessage ? <p className="text-sm text-green-700">{twoFactorMessage}</p> : null}
        {twoFactorError ? <p className="text-sm text-red-600">{twoFactorError}</p> : null}
      </section>
    </div>
  );
}
