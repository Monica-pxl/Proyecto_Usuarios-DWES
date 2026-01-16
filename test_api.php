#!/usr/bin/env php
<?php

/**
 * Script de prueba para el sistema de autenticación API
 *
 * Uso:
 *   php test_api.php
 */

$baseUrl = 'http://localhost:8000';
$testUser = [
    'nombre' => 'Usuario Test',
    'correo' => 'test_' . time() . '@example.com',
    'password' => 'test123456'
];

echo "🧪 Iniciando pruebas de API de autenticación\n";
echo str_repeat('=', 60) . "\n\n";

// 1. Registrar usuario
echo "1️⃣ Registrando usuario...\n";
$response = makeRequest('POST', '/api/register', $testUser);
echo "   ✅ Usuario registrado: " . $response['user']['correo'] . "\n";
echo "   ID: " . $response['user']['id'] . "\n\n";

sleep(1);

// 2. Intentar acceder a perfil sin token (debe fallar)
echo "2️⃣ Intentando acceder a perfil sin token...\n";
try {
    makeRequest('GET', '/api/perfil');
    echo "   ❌ ERROR: Debería haber fallado\n\n";
} catch (Exception $e) {
    echo "   ✅ Falló correctamente: " . $e->getMessage() . "\n\n";
}

sleep(1);

// 3. Login
echo "3️⃣ Iniciando sesión...\n";
$loginData = [
    'correo' => $testUser['correo'],
    'password' => $testUser['password']
];
$loginResponse = makeRequest('POST', '/api/login', $loginData);
$token = $loginResponse['token'];
echo "   ✅ Login exitoso\n";
echo "   Token: " . substr($token, 0, 20) . "...\n";
echo "   Usuario: " . $loginResponse['user']['nombre'] . "\n\n";

sleep(1);

// 4. Acceder a perfil con token
echo "4️⃣ Accediendo a perfil con token...\n";
$perfilResponse = makeRequest('GET', '/api/perfil', null, $token);
echo "   ✅ Perfil obtenido\n";
echo "   Nombre: " . $perfilResponse['user']['nombre'] . "\n";
echo "   Email: " . $perfilResponse['user']['correo'] . "\n";
echo "   Estado: " . ($perfilResponse['user']['estado'] ? 'Activo' : 'Inactivo') . "\n\n";

sleep(1);

// 5. Logout
echo "5️⃣ Cerrando sesión...\n";
$logoutResponse = makeRequest('POST', '/api/logout', null, $token);
echo "   ✅ " . $logoutResponse['message'] . "\n\n";

sleep(1);

// 6. Intentar acceder a perfil después del logout (debe fallar)
echo "6️⃣ Intentando acceder a perfil después del logout...\n";
try {
    makeRequest('GET', '/api/perfil', null, $token);
    echo "   ❌ ERROR: Debería haber fallado\n\n";
} catch (Exception $e) {
    echo "   ✅ Falló correctamente: " . $e->getMessage() . "\n\n";
}

echo str_repeat('=', 60) . "\n";
echo "✅ Todas las pruebas completadas exitosamente!\n\n";

// Función auxiliar para hacer requests
function makeRequest($method, $path, $data = null, $token = null) {
    global $baseUrl;

    $ch = curl_init($baseUrl . $path);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        throw new Exception("Error de conexión. ¿Está el servidor corriendo en $baseUrl?");
    }

    $result = json_decode($response, true);

    if ($httpCode >= 400) {
        throw new Exception($result['error'] ?? 'Error desconocido', $httpCode);
    }

    return $result;
}
