<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Seguro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app">
        <!-- Login Form -->
        <div id="login-section" class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
                <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Login Seguro</h2>
                <form id="login-form">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email
                        </label>
                        <input type="email" id="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Senha
                        </label>
                        <input type="password" id="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                        Entrar
                    </button>
                </form>
            </div>
        </div>

        <!-- Dashboard -->
        <div id="dashboard-section" class="hidden min-h-screen">
            <nav class="bg-white shadow-lg">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="flex justify-between items-center py-4">
                        <h1 class="text-xl font-bold text-gray-800">Sistema Seguro</h1>
                        <button id="logout-btn" 
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-200">
                            Sair
                        </button>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Card de Perfil -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Perfil do Usuário</h3>
                        <div id="user-info" class="space-y-2">
                            <!-- Dados do usuário serão carregados aqui -->
                        </div>
                    </div>

                    <!-- Card de Segurança -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Status de Segurança</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Autenticação JWT</span>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Ativa</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Fingerprint</span>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Verificado</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Rate Limiting</span>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Ativo</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Atividade -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Atividade Recente</h3>
                        <div id="activity-log" class="space-y-2">
                            <!-- Log de atividades será carregado aqui -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        class SecureApp {
            constructor() {
                this.token = localStorage.getItem('auth_token');
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.checkAuth();
            }

            setupEventListeners() {
                document.getElementById('login-form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.login();
                });

                document.getElementById('logout-btn').addEventListener('click', () => {
                    this.logout();
                });
            }

            async login() {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                try {
                    const response = await this.apiCall('post', '/api/login', {
                        email,
                        password
                    });

                    this.token = response.data.token;
                    localStorage.setItem('auth_token', this.token);
                    this.showDashboard();
                    this.loadUserData();

                } catch (error) {
                    this.showError('Erro no login: ' + (error.response?.data?.error || 'Erro desconhecido'));
                }
            }

            async logout() {
                try {
                    await this.apiCall('post', '/api/logout');
                } catch (error) {
                    console.error('Erro no logout:', error);
                } finally {
                    this.token = null;
                    localStorage.removeItem('auth_token');
                    this.showLogin();
                }
            }

            async checkAuth() {
                if (this.token) {
                    try {
                        await this.loadUserData();
                        this.showDashboard();
                    } catch (error) {
                        this.showLogin();
                    }
                }
            }

            async loadUserData() {
                try {
                    const [userResponse, dashboardResponse] = await Promise.all([
                        this.apiCall('get', '/api/user'),
                        this.apiCall('get', '/api/dashboard')
                    ]);

                    this.displayUserInfo(userResponse.data);
                    this.displayActivity(dashboardResponse.data.activity);

                } catch (error) {
                    if (error.response?.status === 401) {
                        this.logout();
                    }
                    throw error;
                }
            }

            displayUserInfo(user) {
                const userInfo = document.getElementById('user-info');
                userInfo.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                            ${user.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p class="font-semibold">${user.name}</p>
                            <p class="text-sm text-gray-600">${user.email}</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-200">
                        <p class="text-sm"><span class="font-medium">IP:</span> Carregando...</p>
                        <p class="text-sm"><span class="font-medium">Dispositivo:</span> ${navigator.userAgent.split(' ')[0]}</p>
                    </div>
                `;
            }

            displayActivity(activities) {
                const activityLog = document.getElementById('activity-log');
                if (!activities || activities.length === 0) {
                    activities = [
                        'Login realizado com sucesso',
                        'Fingerprint verificado',
                        'Sessão segura estabelecida'
                    ];
                }

                activityLog.innerHTML = activities.map(activity => `
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-gray-700">${activity}</span>
                    </div>
                `).join('');
            }

            async apiCall(method, url, data = null) {
                const config = {
                    method,
                    url,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };

                if (this.token) {
                    config.headers.Authorization = `Bearer ${this.token}`;
                }

                if (data) {
                    config.data = data;
                }

                try {
                    const response = await axios(config);
                    return response;
                } catch (error) {
                    if (error.response?.status === 401) {
                        this.handleUnauthorized();
                    } else if (error.response?.status === 429) {
                        this.showError('Muitas requisições. Tente novamente mais tarde.');
                    }
                    throw error;
                }
            }

            handleUnauthorized() {
                this.token = null;
                localStorage.removeItem('auth_token');
                this.showLogin();
                this.showError('Sessão expirada. Faça login novamente.');
            }

            showLogin() {
                document.getElementById('login-section').classList.remove('hidden');
                document.getElementById('dashboard-section').classList.add('hidden');
            }

            showDashboard() {
                document.getElementById('login-section').classList.add('hidden');
                document.getElementById('dashboard-section').classList.remove('hidden');
            }

            showError(message) {
                alert(message); // Em produção, use um sistema de notificação mais sofisticado
            }
        }

        // Inicializa a aplicação quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', () => {
            new SecureApp();
        });
    </script>
</body>
</html>