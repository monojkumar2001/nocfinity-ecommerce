/**
 * Client-side mirrors of backend PasswordRules / shipping rules.
 * Backend remains the source of truth.
 */

export const PASSWORD_HINT =
  "Must include upper, lower, number, and special character (8+ chars).";

export function validatePassword(password: string, confirmation?: string): string | null {
  if (password.length < 8) {
    return "Password must be at least 8 characters.";
  }
  if (!/[A-Z]/.test(password)) {
    return "Password must include an uppercase letter.";
  }
  if (!/[a-z]/.test(password)) {
    return "Password must include a lowercase letter.";
  }
  if (!/[0-9]/.test(password)) {
    return "Password must include a number.";
  }
  if (!/[^A-Za-z0-9]/.test(password)) {
    return "Password must include a special character.";
  }
  if (confirmation !== undefined && password !== confirmation) {
    return "Password confirmation does not match.";
  }
  return null;
}

export function validateOptionalPassword(
  password: string,
  confirmation: string,
): string | null {
  if (!password) return null;
  return validatePassword(password, confirmation);
}

export type ShippingFields = {
  name: string;
  phone: string;
  line1: string;
  city: string;
  state: string;
  postal_code: string;
  country: string;
};

export function validateShippingAddress(address: ShippingFields): string | null {
  if (!address.name.trim()) return "Name is required.";
  if (!address.phone.trim()) return "Phone is required.";
  if (!address.line1.trim()) return "Address line 1 is required.";
  if (!address.city.trim()) return "City is required.";
  if (!address.state.trim()) return "State / region is required.";
  if (!address.postal_code.trim()) return "Postal code is required.";
  if (!address.country.trim()) return "Country is required.";
  return null;
}

export function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}
