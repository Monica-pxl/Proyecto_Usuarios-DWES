# Ejemplos de uso de la API con JavaScript

## 📱 Clase de servicio para autenticación

```javascript
class AuthService {
    constructor(baseURL = '') {
        this.baseURL = baseURL;
        this.tokenKey = 'auth_token';
        this.userKey = 'user';
    }

    // Guardar token en localStorage
    saveToken(token) {
        localStorage.setItem(this.tokenKey, token);
    }

    // Obtener token de localStorage
    getToken() {
        return localStorage.getItem(this.tokenKey);
    }

    // Eliminar token
    removeToken() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
    }

    // Verificar si hay token
    isAuthenticated() {
        return !!this.getToken();
    }

    // Headers con autenticación
    getAuthHeaders() {
        const headers = {
            'Content-Type': 'application/json',
        };
        
        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        return headers;
    }

    // Registrar usuario
    async register(nombre, correo, password) {
        const response = await fetch(`${this.baseURL}/api/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ nombre, correo, password })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error al registrar');
        }

        return data;
    }

    // Login
    async login(correo, password) {
        const response = await fetch(`${this.baseURL}/api/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ correo, password })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error al iniciar sesión');
        }

        // Guardar token y usuario
        this.saveToken(data.token);
        localStorage.setItem(this.userKey, JSON.stringify(data.user));

        return data;
    }

    // Logout
    async logout() {
        const response = await fetch(`${this.baseURL}/api/logout`, {
            method: 'POST',
            headers: this.getAuthHeaders()
        });

        const data = await response.json();

        if (response.ok) {
            this.removeToken();
        }

        return data;
    }

    // Obtener perfil
    async getPerfil() {
        const response = await fetch(`${this.baseURL}/api/perfil`, {
            method: 'GET',
            headers: this.getAuthHeaders()
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401) {
                this.removeToken();
            }
            throw new Error(data.error || 'Error al obtener perfil');
        }

        return data;
    }

    // Obtener usuario guardado
    getUser() {
        const userStr = localStorage.getItem(this.userKey);
        return userStr ? JSON.parse(userStr) : null;
    }
}
```

---

## 🎯 Ejemplos de uso

### 1. Registro de usuario

```javascript
const auth = new AuthService();

// Formulario de registro
document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value;
    
    try {
        const result = await auth.register(nombre, correo, password);
        console.log('Usuario registrado:', result);
        alert('Registro exitoso! Ahora puedes iniciar sesión');
        // Redirigir a login o hacer auto-login
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});
```

### 2. Login

```javascript
const auth = new AuthService();

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value;
    
    try {
        const result = await auth.login(correo, password);
        console.log('Login exitoso:', result);
        console.log('Token:', result.token);
        
        // Redirigir al home o dashboard
        window.location.href = '/';
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});
```

### 3. Obtener perfil del usuario

```javascript
const auth = new AuthService();

async function cargarPerfil() {
    try {
        const result = await auth.getPerfil();
        console.log('Perfil:', result.user);
        
        // Mostrar datos en el DOM
        document.getElementById('user-name').textContent = result.user.nombre;
        document.getElementById('user-email').textContent = result.user.correo;
        document.getElementById('user-status').textContent = 
            result.user.estado ? 'Activo' : 'Inactivo';
    } catch (error) {
        console.error('Error:', error);
        // Si no está autenticado, redirigir al login
        window.location.href = '/login';
    }
}

// Cargar perfil al cargar la página
if (auth.isAuthenticated()) {
    cargarPerfil();
}
```

### 4. Logout

```javascript
const auth = new AuthService();

document.getElementById('logout-btn').addEventListener('click', async () => {
    try {
        await auth.logout();
        console.log('Sesión cerrada');
        window.location.href = '/';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        // Aún así limpiar el token local
        auth.removeToken();
        window.location.href = '/';
    }
});
```

### 5. Petición a cualquier endpoint protegido

```javascript
const auth = new AuthService();

async function hacerPeticionProtegida() {
    try {
        const response = await fetch('/api/alguna-ruta', {
            method: 'GET',
            headers: auth.getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            if (response.status === 401) {
                // Token inválido o expirado
                auth.removeToken();
                window.location.href = '/';
            }
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}
```

---

## 🔄 Interceptor para axios (alternativa)

Si usas **axios** en lugar de fetch:

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api'
});

// Interceptor de request - añade el token automáticamente
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Interceptor de response - maneja errores 401
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token inválido o expirado
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

// Uso:
async function login(correo, password) {
    const { data } = await api.post('/login', { correo, password });
    localStorage.setItem('auth_token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    return data;
}

async function getPerfil() {
    const { data } = await api.get('/perfil');
    return data;
}
```

---

## 🎨 Ejemplo completo en React

```javascript
import { useState, useEffect, createContext, useContext } from 'react';

// Context de autenticación
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(localStorage.getItem('auth_token'));
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (token) {
            // Cargar datos del usuario
            fetchPerfil();
        } else {
            setLoading(false);
        }
    }, [token]);

    const fetchPerfil = async () => {
        try {
            const response = await fetch('/api/perfil', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                setUser(data.user);
            } else {
                logout();
            }
        } catch (error) {
            console.error('Error al cargar perfil:', error);
            logout();
        } finally {
            setLoading(false);
        }
    };

    const login = async (correo, password) => {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ correo, password })
        });

        const data = await response.json();

        if (response.ok) {
            setToken(data.token);
            setUser(data.user);
            localStorage.setItem('auth_token', data.token);
            return data;
        } else {
            throw new Error(data.error);
        }
    };

    const register = async (nombre, correo, password) => {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, correo, password })
        });

        const data = await response.json();

        if (response.ok) {
            return data;
        } else {
            throw new Error(data.error);
        }
    };

    const logout = async () => {
        if (token) {
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
        }
        
        setToken(null);
        setUser(null);
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
    };

    return (
        <AuthContext.Provider value={{ user, login, register, logout, loading }}>
            {children}
        </AuthContext.Provider>
    );
}

// Hook personalizado
export function useAuth() {
    return useContext(AuthContext);
}

// Componente de ejemplo
function LoginPage() {
    const { login } = useAuth();
    const [correo, setCorreo] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        
        try {
            await login(correo, password);
            // Redirigir después del login exitoso
        } catch (err) {
            setError(err.message);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {error && <div className="error">{error}</div>}
            <input
                type="email"
                value={correo}
                onChange={(e) => setCorreo(e.target.value)}
                placeholder="Correo"
                required
            />
            <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Contraseña"
                required
            />
            <button type="submit">Iniciar Sesión</button>
        </form>
    );
}

function HomePage() {
    const { user, logout } = useAuth();

    return (
        <div>
            <h1>Bienvenido, {user?.nombre}</h1>
            <p>Email: {user?.correo}</p>
            <button onClick={logout}>Cerrar Sesión</button>
        </div>
    );
}
```

---

## 🛡️ Protección de rutas en React Router

```javascript
import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthContext';

function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <div>Cargando...</div>;
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    return children;
}

// En tu App.js
function App() {
    return (
        <AuthProvider>
            <Routes>
                <Route path="/login" element={<LoginPage />} />
                <Route path="/register" element={<RegisterPage />} />
                
                <Route path="/" element={
                    <ProtectedRoute>
                        <HomePage />
                    </ProtectedRoute>
                } />
            </Routes>
        </AuthProvider>
    );
}
```

---

## 📱 Ejemplo para Vue.js

```javascript
// store/auth.js (Vuex o Pinia)
export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token'),
        user: JSON.parse(localStorage.getItem('user') || 'null')
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token
    },
    
    actions: {
        async login(correo, password) {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ correo, password })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.token = data.token;
                this.user = data.user;
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
            } else {
                throw new Error(data.error);
            }
        },
        
        async logout() {
            if (this.token) {
                await fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.token}`
                    }
                });
            }
            
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
        }
    }
});
```

---

## ✅ Buenas prácticas

1. **Siempre validar el token**: Verifica que el token existe antes de hacer peticiones protegidas
2. **Manejar errores 401**: Cuando el servidor devuelve 401, elimina el token y redirige al login
3. **No almacenar datos sensibles**: El token es suficiente; no guardes la contraseña
4. **HTTPS en producción**: Siempre usa HTTPS para proteger el token en tránsito
5. **Refresh del token**: Considera implementar refresh tokens para sesiones largas
6. **Logout en todas las tabs**: Usa `storage` event listener para sincronizar logout entre pestañas

```javascript
// Sincronizar logout entre pestañas
window.addEventListener('storage', (e) => {
    if (e.key === 'auth_token' && !e.newValue) {
        // Token eliminado en otra pestaña
        window.location.href = '/';
    }
});
```

---

**¡Listo para integrar en tu frontend! 🚀**
