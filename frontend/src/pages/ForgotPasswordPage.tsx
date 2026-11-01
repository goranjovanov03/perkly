import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { forgotPassword, getErrorMessage } from '../services/auth';

const forgotPasswordSchema = z.object({
  email: z.string().email({ message: 'auth.forgotPassword.error.invalidEmail' }),
});

type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;

export function ForgotPasswordPage() {
  const { t } = useTranslation();
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]> | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  const onSubmit = async (data: ForgotPasswordFormData) => {
    setError(null);
    setSuccess(null);
    setFieldErrors(null);

    try {
      const result = await forgotPassword(data);
      setSuccess(result.message);
    } catch (err) {
      setError(getErrorMessage(err, t('auth.forgotPassword.error.title')));
      if (err && typeof err === 'object' && 'error' in err) {
        const apiError = err as { error?: { fields?: Record<string, string[]> } };
        if (apiError.error?.fields) {
          setFieldErrors(apiError.error.fields);
        }
      }
    }
  };

  return (
    <div style={styles.container}>
      <div style={styles.card}>
        <h1 style={styles.title}>{t('auth.forgotPassword.title')}</h1>
        <p style={styles.description}>{t('auth.forgotPassword.description')}</p>

        {error && (
          <div style={styles.error}>
            {error}
          </div>
        )}

        {success && (
          <div style={styles.success}>
            {success}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} style={styles.form}>
          <div style={styles.field}>
            <label htmlFor="email" style={styles.label}>
              {t('auth.forgotPassword.email')}
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
                {t(errors.email.message as string)}
              </span>
            )}
            {fieldErrors?.email && (
              <span style={styles.fieldError}>
                {fieldErrors.email[0]}
              </span>
            )}
          </div>

          <button type="submit" style={styles.button} disabled={isSubmitting}>
            {isSubmitting ? t('common.loading') : t('auth.forgotPassword.submit')}
          </button>
        </form>

        <div style={styles.links}>
          <Link to="/login" style={styles.link}>
            {t('auth.forgotPassword.backToLogin')}
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
    marginBottom: '0.5rem',
    textAlign: 'center',
    color: '#333',
  },
  description: {
    marginBottom: '1.5rem',
    textAlign: 'center',
    color: '#666',
  },
  error: {
    marginBottom: '1rem',
    padding: '0.75rem',
    backgroundColor: '#fee',
    border: '1px solid #fcc',
    borderRadius: '4px',
    color: '#c00',
  },
  success: {
    marginBottom: '1rem',
    padding: '0.75rem',
    backgroundColor: '#efe',
    border: '1px solid #cfc',
    borderRadius: '4px',
    color: '#060',
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