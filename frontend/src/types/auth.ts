export interface User {
  id: string;
  name: string;
  email: string;
  onboarded_at: string | null;
}

export interface Organization {
  id: string;
  name: string;
  slug: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface ForgotPasswordData {
  email: string;
}

export interface ResetPasswordData {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface ApiError {
  error: {
    code: string;
    message: string;
    request_id: string;
    fields?: Record<string, string[]>;
  };
}

export interface AuthState {
  user: User | null;
  organizations: Organization[];
  selectedOrganizationId: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  sessionExpired: boolean;
}

export const ORGANIZATION_STORAGE_KEY = 'selectedOrganizationId';
export const SESSION_EXPIRED_KEY = 'sessionExpired';