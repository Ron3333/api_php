// js/admin.js
const API_BASE = 'http://restaurante-api.test/api-admin';  // API
const ADMIN_BASE = 'http://restaurante-api.test/admin-panel';             // Frontend

function getToken() {
    return localStorage.getItem('adminToken');
}

function setToken(token) {
    localStorage.setItem('adminToken', token);
}

function removeToken() {
    localStorage.removeItem('adminToken');
}

function isLoggedIn() {
    return !!getToken();
}

async function apiCall(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (isLoggedIn()) {
        headers['Authorization'] = `Bearer ${getToken()}`;
    }

    let body = options.body;

    // SIEMPRE convertir a JSON (incluso si viene de FormData)
    if (body instanceof FormData) {
        const obj = {};
        for (const [key, value] of body.entries()) {
            obj[key] = value;
        }
        body = JSON.stringify(obj);
    } else if (body && typeof body === 'object') {
        body = JSON.stringify(body);
    }

    const res = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers,
        body
    });

    let data = {};
    try {
        data = await res.json();
    } catch (e) {
        data = { message: 'Respuesta no válida del servidor' };
    }

    if (!res.ok) {
        if (res.status === 401) {
            removeToken();
            window.location.href = `${ADMIN_BASE}/admins/login-admins.php`;
        }
        throw new Error(data.message || 'Error en la solicitud');
    }

    return data;
}

// apiCallFile: para subir archivos (FormData) sin convertir a JSON
async function apiCallFile(endpoint, formData, options = {}) {
    const headers = {
        ...options.headers
    };

    if (isLoggedIn()) {
        headers['Authorization'] = `Bearer ${getToken()}`;
    }

    // NO establecer Content-Type → el navegador lo genera con boundary
    // delete headers['Content-Type'];


    const res = await fetch(`${API_BASE}${endpoint}`, {
        method: 'POST',
        headers,
        body: formData
    });

    let data = {};
    try {
        data = await res.json();
    } catch (e) {
        data = { message: 'Error del servidor' };
    }

    if (!res.ok) {
        if (res.status === 401) {
            removeToken();
            window.location.href = `${ADMIN_BASE}/admins/login-admins.php`;
        }
        throw new Error(data.message || 'Error en la solicitud');
    }

    return data;
}

async function apiCallFileUpdate(endpoint, formData, options = {}) {
    const headers = {};
    if (isLoggedIn()) {
        headers['Authorization'] = `Bearer ${getToken()}`;
    }

    const res = await fetch(`${API_BASE}${endpoint}`, {
        method: options.method || 'POST',
        headers,
        body: formData
    });

    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.message || 'Error en la solicitud');
    }
    return data;
}

function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">×</button>
    `;
    document.body.prepend(alert);
    setTimeout(() => alert.remove(), 5000);
}