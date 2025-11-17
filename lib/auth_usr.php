<?php
declare(strict_types=1);
namespace App\Lib;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

//Funcion para el envio de correo
function sendEmail(string $to, string $subject, string $body, ?string $attachmentData = null, ?string $attachmentName = null) : bool {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // <-- APAGADO
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME']; // Lee de .env
        $mail->Password = $_ENV['MAIL_PASSWORD']; // Lee de .env
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = (int)($_ENV['MAIL_PORT'] ?? 587);

        $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME'] ?? 'Las Sevillanas');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($attachmentData && $attachmentName) {
            $mail->addStringAttachment($attachmentData, $attachmentName, 'base64', 'application/pdf');
        }

        $mail->send();
        return true;
    } catch(Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

// ===================================
// FUNCIONES DE SESIÓN Y REGISTRO
// ===================================

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true,  // Asumir HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function registerUser(string $nombre, string $apellido, string $email, string $password) {
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new \Exception('El correo electrónico ya está registrado.');
    }

    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        INSERT INTO usuario (nombre, apellido, email, password, verification_token) 
        VALUES (:nombre, :apellido, :email, :password, :verification_token)");
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':email' => $email,
        ':password' => $passwordHash,
        ':verification_token' => $token,
    ]);

    $verificationLink = "http://{$_SERVER['HTTP_HOST']}/LasSevillanas/Proyectini/users/verify.php?token={$token}";
    $emailBody = "<h1>Bienvenido a Las Sevillanas</h1>";
    $emailBody .= "<p>Gracias por registrarte. Por favor, haz click en el siguiente enlace para activar tu cuenta:</p>";
    $emailBody .= "<a href='{$verificationLink}'>Activar mi cuenta</a>";

    return sendEmail($email, 'Activa tu cuenta en las Sevillanas', $emailBody);
}

function verifyAccount(string $token) : bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id_usuario
        FROM usuario
        WHERE verification_token = ?
        AND is_verified = FALSE
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if($user){
        $stmt = $pdo->prepare("
            UPDATE usuario
            SET is_verified = TRUE, verification_token = NULL
            WHERE id_usuario = ?
        ");
        return $stmt->execute([$user['id_usuario']]);
    }
    return false;
}

function isLoggedIn() : bool {
    return isset($_SESSION['user_id']);
}

function attemptLogin(string $email, string $password) : bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT id_usuario, password, is_verified
        FROM usuario WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user && $user['is_verified'] && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id_usuario'];
        return true;
    }
    return false;
}

function logout() : void {
    $_SESSION = [];
    if(ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function getUserById(int $userId): ?array
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre, apellido, email 
            FROM usuario WHERE id_usuario = ?
            ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

/**
 * Actualiza los detalles (nombre, apellido) y preferencias de un usuario.
 */
function updateUserDetails(int $userId, string $nombre, string $apellido): bool
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            UPDATE usuario SET nombre = ?, apellido = ? 
            WHERE id_usuario = ?
            ");
        return $stmt->execute([$nombre, $apellido, $userId]);
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Cambia la contraseña de un usuario verificando la antigua.
 */
function changeUserPassword(int $userId, string $oldPassword, string $newPassword): bool
{
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("SELECT password FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return false;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("UPDATE usuario SET password = ? WHERE id_usuario = ?");
        return $stmt->execute([$newPasswordHash, $userId]);

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}
    /**
    *  Elimina la cuenta de un usuario tras verificar su contraseña.
    * (Cumple con el derecho de Cancelación)
    */
    function deleteUserAccount(int $userId, string $password): bool
{
    try {
        $pdo = getPDO();
        
        // 1. Obtener el hash actual para verificar la contraseña
        $stmt = $pdo->prepare("SELECT password FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            // La contraseña no coincide
            return false;
        }

        // 2. Si coincide, proceder a eliminar el usuario
        // La BDD está configurada con 'ON DELETE SET NULL' para los pedidos,
        // así que los registros de pedidos se conservarán como anónimos.
        $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        return $stmt->execute([$userId]);

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

    /**
    * Inicia el proceso de restablecimiento de contraseña.
    * Genera un token y envía el correo al usuario.
    */
    function initiatePasswordReset(string $email) : bool {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ? AND is_verified = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                // El token expira en 1 hora
                $expires = (new \DateTime())
                    ->add(new \DateInterval('PT1H'))
                    ->format('Y-m-d H:i:s');
            
                $stmt = $pdo->prepare("
                    UPDATE usuario 
                    SET reset_token = ?, reset_token_expires_at = ? 
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$token, $expires, $user['id_usuario']]);

                // Construir el enlace
                $resetLink = "http://{$_SERVER['HTTP_HOST']}/LasSevillanas/Proyectini/users/reset_password.php?token={$token}";
                $emailBody = "<h1>Restablecer Contraseña</h1>";
                $emailBody .= "<p>Hemos recibido una solicitud para restablecer tu contraseña. Si no fuiste tú, ignora este correo.</p>";
                $emailBody .= "<p>Haz clic en el siguiente enlace para crear una nueva contraseña (expira en 1 hora):</p>";
                $emailBody .= "<a href='{$resetLink}' style='padding: 10px 15px; background-color: #C60969; color: white; text-decoration: none; border-radius: 5px;'>Restablecer mi contraseña</a>";
                $emailBody .= "<p>O copia y pega esta URL en tu navegador:<br>{$resetLink}</p>";

                // Usar tu función de correo existente
                return sendEmail($email, 'Restablece tu contraseña en Las Sevillanas', $emailBody);
            }
        
            return true; 
        
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
    * Verifica si un token de restablecimiento es válido y no ha expirado.
    */
    function verifyResetToken(string $token) : ?int {
        try {
            $pdo = getPDO();
            // Busca un token que coincida Y que no haya expirado
            $stmt = $pdo->prepare("
                SELECT id_usuario FROM usuario 
                WHERE reset_token = ? AND reset_token_expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
        
            return $user ? (int)$user['id_usuario'] : null;

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
    * Establece la nueva contraseña para un usuario usando el ID y limpia el token.
    */
    function resetPasswordWithToken(int $userId, string $newPassword): bool {
        try {
            $pdo = getPDO();
            $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
            // Actualiza la contraseña y anula el token para que no se reutilice
            $stmt = $pdo->prepare("
                UPDATE usuario 
                SET password = ?, reset_token = NULL, reset_token_expires_at = NULL
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$newPasswordHash, $userId]);

        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
