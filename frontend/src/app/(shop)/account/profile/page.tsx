"use client";

import { useEffect, useState } from "react";
import { apiRequest } from "@/lib/api";
import { fetchCurrentUser, getUser, setAuth, getToken } from "@/lib/auth";
import type { User } from "@/lib/types";
import { Input } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { Spinner } from "@/components/ui/Spinner";

export default function AccountProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const cached = getUser();
    if (cached) {
      setUser(cached);
      setName(cached.name);
      setEmail(cached.email);
    }
    fetchCurrentUser()
      .then((u) => {
        setUser(u);
        setName(u.name);
        setEmail(u.email);
      })
      .catch(() => undefined);
  }, []);

  async function saveProfile(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);
    setError(null);
    try {
      const { data, message: msg } = await apiRequest<{ user: User }>("/profile", {
        method: "PUT",
        body: JSON.stringify({ name, email }),
      });
      const token = getToken();
      if (token) setAuth(token, data.user);
      setUser(data.user);
      setMessage(msg || "Profile updated.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Update failed");
    } finally {
      setSaving(false);
    }
  }

  if (!user) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-8 w-8" />
      </div>
    );
  }

  return (
    <div className="max-w-lg">
      <h2 className="font-display text-2xl font-bold">Profile</h2>
      <p className="mt-1 text-sm text-muted-foreground">Update your name and email.</p>

      <form onSubmit={saveProfile} className="mt-6 space-y-4 rounded-2xl border border-border bg-white p-6">
        <Input label="Full name" required value={name} onChange={(e) => setName(e.target.value)} />
        <Input
          label="Email"
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        {message ? <p className="text-sm text-emerald-700">{message}</p> : null}
        {error ? <p className="text-sm text-red-600">{error}</p> : null}
        <Button type="submit" disabled={saving}>
          {saving ? "Saving…" : "Save changes"}
        </Button>
      </form>
    </div>
  );
}
