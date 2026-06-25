import axios from 'axios';
import { message } from "ant-design-vue";

var axiosPdf = axios.create({
    baseURL: window.config.path + '/api/v1'
});

// Axios default headers
axiosPdf.defaults.headers['Accept'] = 'application/json';
axiosPdf.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axiosPdf.defaults.headers.common['X-CSRF-TOKEN'] = document.head.querySelector('meta[name="csrf-token"]').content;

// Axios jwt token by default
if (localStorage.getItem('auth_token')) {
    axiosPdf.defaults.headers.common['Authorization'] = 'Bearer ' + localStorage.getItem('auth_token');
}
// Helper: parse error message from response data (handles JSON and Blob types)
async function parseErrorMessage(responseData) {
    try {
        if (responseData instanceof Blob) {
            const text = await responseData.text();
            const json = JSON.parse(text);
            return json?.error?.message || json?.message || 'An error occurred';
        }
        return responseData?.error?.message || responseData?.message || 'An error occurred';
    } catch (e) {
        return 'An error occurred';
    }
}

// Axios error listener
axiosPdf.interceptors.response.use(function (response) {
    return response;
}, async function (error) {
    const errorCode = error.response?.status;

    if (errorCode === 401) {
        // If error 401 redirect to login
        window.location.href = window.config.path + '/admin/login';
        delete window.axiosPdf.defaults.headers.common.Authorization;
        localStorage.removeItem('auth_token');
        localStorage.setItem('auth_user', null);
    } else if (errorCode === 400 || errorCode === 403 || errorCode === 404) {
        const errMessage = await parseErrorMessage(error.response.data);
        message.error(errMessage);
    }

    return Promise.reject(error.response);
});

/**
 * Set global so you don't have to import it
 */
window.axiosPdf = axiosPdf;
