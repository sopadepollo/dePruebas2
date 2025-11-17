<?php
// EN: lib/auth.php

declare(strict_types=1);

// 1. Cambiamos el namespace para evitar conflictos
namespace App\Lib\Admin; 

require_once __DIR__ . '/db.php';

// 2. Incluimos auth_usr.php para usar la función de conexión a la BD
require_once __DIR__ . '/auth_usr.php';
// 3. Importamos la función de conexión
use function App\Lib\getDbConnection;
use function App\Lib\getPDO;

const SESSION_KEY = 'dulces_admin_session'; // Mantenemos una clave de sesión separada

function ensureSession(): void
{
    // 4. Usamos la función "inteligente"
    \App\Lib\startSecureSession();
}

function isLoggedIn(): bool
{
    ensureSession();
    return isset($_SESSION[SESSION_KEY]) && $_SESSION[SESSION_KEY] === true;
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Intenta loguear a un administrador verificando la BD.
 * Devuelve un CÓDIGO DE ESTADO en lugar de un booleano.
 *
 * @param string $email El email del administrador.
 * @param string $password La contraseña.
 * @return string ('SUCCESS', 'NOT_FOUND', 'WRONG_PASS', 'NO_PERMISSIONS', 'DB_ERROR')
 */
function attemptLogin(string $email, string $password): string
{
    // Roles permitidos para entrar al panel de admin
    $allowed_roles = ['Gerente', 'Supervisor', 'Admin']; // Ajusta esto según tu BD

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id_usuario, password, cargo FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 1. Verificar si el usuario existe
        if (!$user) {
            return 'NOT_FOUND';
        }

        // 2. Verificar si la contraseña coincide
        if (!password_verify($password, $user['password'])) {
            return 'WRONG_PASS';
        }
        
        // 3. Verificar si el 'cargo' está en la lista de admins permitidos
        if (!in_array($user['cargo'], $allowed_roles)) {
            return 'NO_PERMISSIONS';
        }
        
        // 4. ¡Éxito!
        ensureSession();
        $_SESSION[SESSION_KEY] = true; 
        $_SESSION['admin_user_id'] = $user['id_usuario'];
        return 'SUCCESS';

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return 'DB_ERROR';
    }
}

function logout(): void
{
    ensureSession();
    unset($_SESSION[SESSION_KEY]);
    unset($_SESSION['admin_user_id']);
}