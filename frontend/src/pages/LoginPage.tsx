import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';

const loginSchema = z.object({
  email: z.string().email({ message: 'auth.login.error.invalidCredentials' }),
  password: z.string().min(1, { message: 'auth.login.error.invalidCredentials' }),
});

type LoginFormData = z.infer<typeof loginSchema>;

export function LoginPage() {
  const { t } = useTranslation();
  const { login, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]> | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const onSubmit = async (data: LoginFormData) => {
    setError(null);
    setFieldErrors(null);

    const result = await login(data);
    if (result.error) {
      setError(result.error);
      if (result.fieldErrors) {
        setFieldErrors(result.fieldErrors);
      }
    } else {
      navigate('/');
    }
  };

  if (isAuthenticated) {
    navigate('/');
    return null;
  }

  return (
    <div style={styles.container}>
      <div style={styles.card}>
        <h1 style={styles.title}>{t('auth.login.title')}</h1>

        {error && (
          <div style={styles.error}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} style={styles.form}>
          <div style={styles.field}>
            <label htmlFor="email" style={styles.label}>
              {t('auth.login.email')}
            </label>
            <input
              id="email"
              type="email"
              {...register('email')}
              style={{ ...styles.input, ...(errors.email ? styles.inputError : {}) }}
              disabled={isSubmitting}
              autoComplete="email"
            />
            {errors.email && (
              <span style={styles.fieldError}>
                {t('auth.login.error.invalidCredentials')}
              </span>
            )}
            {fieldErrors?.email && (
              <span style={styles.fieldError}>
                {fieldErrors.email[0]}
              </span>
            )}
          </div>

          <div style={styles.field}>
            <label htmlFor="password" style={styles.label}>
              {t('auth.login.password')}
            </label>
            <input
              id="password"
              type="password"
              {...register('password')}
              style={{ ...styles.input, ...(errors.password ? styles.inputError : {}) }}
              disabled={isSubmitting}
              autoComplete="current-password"
            />
            {errors.password && (
              <span style={styles.fieldError}>
                {t('auth.login.error.invalidCredentials')}
              </span>
            )}
            {fieldErrors?.password && (
              <span style={styles.fieldError}>
                {fieldErrors.password[0]}
              </span>
            )}
          </div>

          <button type="submit" style={styles.button} disabled={isSubmitting}>
            {isSubmitting ? t('common.loading') : t('auth.login.submit')}
          </button>
        </form>

        <div style={styles.links}>
          <Link to="/forgot-password" style={styles.link}>
            {t('auth.login.forgotPassword')}
          </Link>
        </div>
      </div>
    </div>
  );
}

const styles: Record<string, React.CSSProperties> = {
  container: {
    minHeight: '100vh',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '1rem',
    backgroundColor: '#f5f5f5',
  },
  card: {
    width: '100%',
    maxWidth: '400px',
    padding: '2rem',
    backgroundColor: 'white',
    borderRadius: '8px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
  },
  title: {
    marginBottom: '1.5rem',
    textAlign: 'center',
    color: '#333',
  },
  error: {
    marginBottom: '1rem',
    padding: '0.75rem',
    backgroundColor: '#fee',
    border: '1px solid #fcc',
    borderRadius: '4px',
    color: '#c00',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '1rem',
  },
  field: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.25rem',
  },
  label: {
    fontWeight: 500,
    color: '#333',
  },
  input: {
    padding: '0.75rem',
    border: '1px solid #ddd',
    borderRadius: '4px',
    fontSize: '1rem',
  },
  inputError: {
    borderColor: '#c00',
  },
  fieldError: {
    fontSize: '0.875rem',
    color: '#c00',
  },
  button: {
    marginTop: '0.5rem',
    padding: '0.75rem',
    backgroundColor: '#0066cc',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    fontSize: '1rem',
    fontWeight: 500,
    cursor: 'pointer',
  },
  links: {
    marginTop: '1.5rem',
    textAlign: 'center',
  },
  link: {
    color: '#0066cc',
    textDecoration: 'none',
  },
};