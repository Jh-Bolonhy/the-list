import { ref } from 'vue';
import axios from 'axios';

export function useAuth() {
  const user = ref(null);
  const loading = ref(true);
  const showRegisterForm = ref(false);
  const loginForm = ref({
    email: '',
    password: ''
  });
  const registerForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  });

  const updateCsrfToken = (token) => {
    const metaTag = document.head.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      metaTag.setAttribute('content', token);
    }
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
  };

  const checkAuth = async () => {
    try {
      const response = await axios.get('/api/user');
      user.value = response.data.user;
      if (response.data.csrf_token) {
        updateCsrfToken(response.data.csrf_token);
      }
    } catch (error) {
      console.error('Error checking auth:', error);
      user.value = null;
    } finally {
      loading.value = false;
    }
  };

  const handleRegister = async (locale = 'en') => {
    try {
      const response = await axios.post('/api/register', {
        ...registerForm.value,
        locale: locale
      });
      user.value = response.data.user;
      if (response.data.csrf_token) {
        updateCsrfToken(response.data.csrf_token);
      }
      registerForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      };
      return { success: true };
    } catch (error) {
      console.error('Error registering:', error);
      let errorMessage = 'Failed to register';
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        errorMessage = Object.values(errors).flat().join(', ');
      }
      return { success: false, error: errorMessage };
    }
  };

  const handleLogin = async () => {
    try {
      const response = await axios.post('/api/login', loginForm.value);
      user.value = response.data.user;
      if (response.data.csrf_token) {
        updateCsrfToken(response.data.csrf_token);
      }
      loginForm.value = {
        email: '',
        password: ''
      };
      return { success: true };
    } catch (error) {
      console.error('Error logging in:', error);
      let errorMessage = 'Failed to login';
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        errorMessage = Object.values(errors).flat().join(', ');
      } else if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
      }
      return { success: false, error: errorMessage };
    }
  };

  const handleLogout = async () => {
    try {
      const response = await axios.post('/api/logout');
      if (response.data.csrf_token) {
        updateCsrfToken(response.data.csrf_token);
      }
      user.value = null;
      showRegisterForm.value = false;
      return { success: true };
    } catch (error) {
      console.error('Error logging out:', error);
      return { success: false, error: 'Failed to logout' };
    }
  };

  return {
    user,
    loading,
    showRegisterForm,
    loginForm,
    registerForm,
    checkAuth,
    handleRegister,
    handleLogin,
    handleLogout,
    updateCsrfToken
  };
}

