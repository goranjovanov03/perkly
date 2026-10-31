import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { resetPassword, getErrorMessage } from '../services/auth';

const resetPasswordSchema = z.object({
  password: z.string().min(8, { message: 'auth.resetPassword.error.passwordTooShort' }),
  password_confirmation: z.string().min(1, { message: 'auth.resetPassword.error.passwordRequired' }),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'auth.resetPassword.error.passwordMismatch',
  path: ['password_confirmation'],
});

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

export function ResetPasswordPage() {
  const { t } = useTranslation();
  const [searchParams] = useSearchParams();
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]> | null>(null);
  const [token, setToken] = useState('');
  const [email, setEmail] = useState('');

  useEffect(() => {
    const tokenParam = searchParams.get('token');
    const emailParam = searchParams.get('email');
    
    if (tokenParam) setToken(tokenParam);
    if (emailParam) setEmail(emailParam);
    
    if (!tokenParam || !emailParam) {
      setError(t('auth.resetPassword.error.invalidToken'));
    }
  }, [searchParams, t]);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
  });

  const onSubmit = async (data: ResetPasswordFormData) => {
    if (!token || !email) return;

    setError(null);
    setSuccess(null);
    setFieldErrors(null);

    try {
      await resetPassword({
        token,
        email,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });
      setSuccess(t('auth.resetPassword.success'));
    } catch (err) {
      setError(getErrorMessage(err, t('auth.resetPassword.error.title')));
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
        <h1 style={styles.title}>{t('auth.resetPassword.title')}</h1>
        <p style={styles.description}>{t('auth.resetPassword.description')}</p>

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
            <label htmlFor="password" style={styles.label}>
              {t('auth.resetPassword.password')}
            </label>
            <input
              id="password"
              type="password"
              {...register('password')}
              style={{ ...styles.input, ...(errors.password ? styles.inputError : {}) }}
              disabled={isSubmitting || !token || !email}
              autoComplete="new-password"
            />
            {errors.password && (
              <span style={styles.fieldError}>
                {t(errors.password.message as string)}
              </span>
            )}
            {fieldErrors?.password && (
              <span style={styles.fieldError}>
                {fieldErrors.password[0]}
              </span>
            )}
          </div>

          <div style={styles.field}>
            <label htmlFor="password_confirmation" style={styles.label}>
              {t('auth.resetPassword.passwordConfirmation')}
            </label>
            <input
              id="password_confirmation"
              type="password"
              {...register('password_confirmation')}
              style={{ ...styles.input, ...(errors.password_confirmation ? styles.inputError : {}) }}
              disabled={isSubmitting || !token || !email}
              autoComplete="new-password"
            />
            {errors.password_confirmation && (
              <span style={styles.fieldError}>
                {t(errors.password_confirmation.message as string)}
              </span>
            )}
            {fieldErrors?.password_confirmation && (
              <span style={styles.fieldError}>
                {fieldErrors.password_confirmation[0]}
              </span>
            )}
          </div>

          <button type="submit" style={styles.button} disabled={isSubmitting || !token || !email}>
            {isSubmitting ? t('common.loading') : t('auth.resetPassword.submit')}
          </button>
        </form>

        <div style={styles.links}>
          <Link to="/login" style={styles.link}>
            {t('auth.resetPassword.backToLogin')}
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