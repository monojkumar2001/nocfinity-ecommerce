"use client";

import { useEffect, useState } from "react";
import { apiRequest } from "@/lib/api";
import { fetchCurrentUser } from "@/lib/auth";
import type { User } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";
import { PASSWORD_HINT, validateOptionalPassword } from "@/lib/validation";

export default function AdminProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [name, setName] = useState("");
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [password_confirmation, setConfirm] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    apiRequest<User>("/admin/me").then(({ data }) => {
      setUser(data);
      setName(data.name);
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

  if (!user) return <Spinner className="mt-12" />;

  return (
    <div className="max-w-md">
      <h1 className="font-display text-2xl font-bold">Profile</h1>
      <p className="text-sm text-muted-foreground">{user.email}</p>
      <form onSubmit={saveProfile} className="mt-6 space-y-4 rounded-xl border border-border bg-white p-6">
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
    </div>
  );
}