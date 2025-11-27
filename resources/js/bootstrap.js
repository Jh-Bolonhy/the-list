import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['Content-Type'] = 'application/json';
window.axios.defaults.withCredentials = true; // Enable cookies for session-based auth

// Set CSRF token for all requests
const updateCsrfToken = () => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
    }
};

// Initial token setup
updateCsrfToken();

// Intercept 419 errors and try to refresh token
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response && error.response.status === 419) {
            // Try to get new token from server
            try {
                const response = await axios.get('/api/csrf-token');
                if (response.data.csrf_token) {
                    const metaTag = document.head.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', response.data.csrf_token);
                    }
                    updateCsrfToken();
                    // Retry the original request
                    return axios.request(error.config);
                }
            } catch (e) {
                console.error('Failed to refresh CSRF token:', e);
            }
        }
        return Promise.reject(error);
    }
);
