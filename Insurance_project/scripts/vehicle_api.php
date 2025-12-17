<?php
/**
 * Vehicle API - Endpoint do zarządzania zapisanymi pojazdami użytkownika
 *
 * OWASP Top 10 Compliance:
 * - A01: Broken Access Control → Session validation, user ownership check
 * - A02: Cryptographic Failures → Secure session handling
 * - A03: Injection → Prepared statements with PDO
 * - A04: Insecure Design → Business logic validation (max 5 vehicles)
 * - A05: Security Misconfiguration → Error handling without info disclosure
 * - A07: Identification and Authentication → Session check required
 * - A08: Software and Data Integrity → CSRF token validation
 *
 * @author Senior PHP Developer
 * @version 1.0
 */

declare(strict_types=1);

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Error handling - don't expose sensitive information in production
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/db_connect.php';

// Constants
const MAX_VEHICLES_PER_USER = 5;
const ALLOWED_VEHICLE_TYPES = ['CAR', 'MOTORCYCLE'];
const ALLOWED_BODY_TYPES = ['Sedan', 'SUV', 'Kombi', 'Hatchback', 'Kompakt', 'Coupe', 'Kabriolet'];
const ALLOWED_FUEL_TYPES = ['BENZYNA', 'ROPA', 'BENZYNA+LPG', 'HYBRYDA', 'PRĄD'];
const ALLOWED_MOTORCYCLE_TYPES = ['naked', 'cruiser', 'bobber', 'cross'];

/**
 * Send JSON response and exit
 */
function sendResponse(bool $success, $data = null, string $message = '', int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validate CSRF token (basic implementation)
 * In production, use a proper CSRF library
 */
function validateCsrfToken(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Timing-safe comparison to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize and validate vehicle data
 */
function validateVehicleData(array $data): array
{
    $errors = [];
    $clean = [];

    // Vehicle Type (required)
    if (empty($data['vehicle_type']) || !in_array($data['vehicle_type'], ALLOWED_VEHICLE_TYPES, true)) {
        $errors[] = 'Nieprawidłowy typ pojazdu';
    } else {
        $clean['vehicle_type'] = $data['vehicle_type'];
    }

    // Brand (required)
    if (empty($data['brand']) || strlen($data['brand']) > 50) {
        $errors[] = 'Marka pojazdu jest wymagana (max 50 znaków)';
    } else {
        $clean['brand'] = trim($data['brand']);
    }

    // Model (optional but recommended)
    $clean['model'] = !empty($data['model']) ? trim(substr($data['model'], 0, 50)) : '';

    // Year (required, 1990-2025)
    $year = filter_var($data['year'] ?? 0, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1990, 'max_range' => 2025]
    ]);
    if ($year === false) {
        $errors[] = 'Rok produkcji musi być z zakresu 1990-2025';
    } else {
        $clean['year'] = $year;
    }

    // Engine Capacity (required, 50-8000 cm³)
    $capacity = filter_var($data['engine_capacity'] ?? 0, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 50, 'max_range' => 8000]
    ]);
    if ($capacity === false) {
        $errors[] = 'Pojemność silnika musi być z zakresu 50-8000 cm³';
    } else {
        $clean['engine_capacity'] = $capacity;
    }

    // Body Type (for cars only)
    if (!empty($data['body_type'])) {
        if (!in_array($data['body_type'], ALLOWED_BODY_TYPES, true)) {
            $errors[] = 'Nieprawidłowy typ nadwozia';
        } else {
            $clean['body_type'] = $data['body_type'];
        }
    }

    // Fuel Type (for cars only)
    if (!empty($data['fuel_type'])) {
        if (!in_array($data['fuel_type'], ALLOWED_FUEL_TYPES, true)) {
            $errors[] = 'Nieprawidłowy rodzaj paliwa';
        } else {
            $clean['fuel_type'] = $data['fuel_type'];
        }
    }

    // Motorcycle Type (for motorcycles only)
    if (!empty($data['motorcycle_type'])) {
        if (!in_array($data['motorcycle_type'], ALLOWED_MOTORCYCLE_TYPES, true)) {
            $errors[] = 'Nieprawidłowy typ motocykla';
        } else {
            $clean['motorcycle_type'] = $data['motorcycle_type'];
        }
    }

    // Power HP (optional, 5-1000 HP)
    if (!empty($data['power_hp'])) {
        $power = filter_var($data['power_hp'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 5, 'max_range' => 1000]
        ]);
        if ($power !== false) {
            $clean['power_hp'] = $power;
        }
    }

    if (!empty($errors)) {
        sendResponse(false, null, implode(', ', $errors), 400);
    }

    return $clean;
}

/**
 * Get all vehicles for current user
 */
function getUserVehicles(PDO $pdo, int $userId): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                Vehicle_ID,
                Vehicle_type,
                Brand,
                Model,
                Year,
                Engine_capacity,
                Power_HP,
                Body_Type,
                Fuel_Type,
                Motorcycle_Type,
                created_at
            FROM Vehicle
            WHERE Users_ID = :user_id
            ORDER BY created_at DESC
        ");

        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Failed to fetch vehicles: " . $e->getMessage());
        sendResponse(false, null, 'Błąd pobierania pojazdów', 500);
    }

    return []; // Never reached, but makes IDE happy
}

/**
 * Add new vehicle for current user
 */
function addVehicle(PDO $pdo, int $userId, array $data): array
{
    try {
        // Check vehicle count limit
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM Vehicle WHERE Users_ID = :user_id");
        $countStmt->execute([':user_id' => $userId]);
        $count = (int)$countStmt->fetchColumn();

        if ($count >= MAX_VEHICLES_PER_USER) {
            sendResponse(false, null, 'Możesz zapisać maksymalnie ' . MAX_VEHICLES_PER_USER . ' pojazdów', 400);
        }

        // Validate data
        $clean = validateVehicleData($data);

        // Insert vehicle
        $stmt = $pdo->prepare("
            INSERT INTO Vehicle (
                Users_ID,
                Vehicle_type,
                Brand,
                Model,
                Year,
                Engine_capacity,
                Power_HP,
                Body_Type,
                Fuel_Type,
                Motorcycle_Type
            ) VALUES (
                :user_id,
                :vehicle_type,
                :brand,
                :model,
                :year,
                :engine_capacity,
                :power_hp,
                :body_type,
                :fuel_type,
                :motorcycle_type
            )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':vehicle_type' => $clean['vehicle_type'],
            ':brand' => $clean['brand'],
            ':model' => $clean['model'] ?? '',
            ':year' => $clean['year'],
            ':engine_capacity' => $clean['engine_capacity'],
            ':power_hp' => $clean['power_hp'] ?? null,
            ':body_type' => $clean['body_type'] ?? null,
            ':fuel_type' => $clean['fuel_type'] ?? null,
            ':motorcycle_type' => $clean['motorcycle_type'] ?? null
        ]);

        $vehicleId = (int)$pdo->lastInsertId();

        // Fetch and return the newly created vehicle
        $fetchStmt = $pdo->prepare("SELECT * FROM Vehicle WHERE Vehicle_ID = :id");
        $fetchStmt->execute([':id' => $vehicleId]);
        $vehicle = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        return $vehicle ?: [];

    } catch (PDOException $e) {
        error_log("Failed to add vehicle: " . $e->getMessage());
        sendResponse(false, null, 'Błąd dodawania pojazdu', 500);
    }

    return []; // Never reached
}

/**
 * Delete vehicle (only if owned by current user)
 */
function deleteVehicle(PDO $pdo, int $userId, int $vehicleId): bool
{
    try {
        // Security: Verify ownership before deleting
        $stmt = $pdo->prepare("
            DELETE FROM Vehicle
            WHERE Vehicle_ID = :vehicle_id
            AND Users_ID = :user_id
        ");

        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;

    } catch (PDOException $e) {
        error_log("Failed to delete vehicle: " . $e->getMessage());
        sendResponse(false, null, 'Błąd usuwania pojazdu', 500);
    }

    return false; // Never reached
}

// ============================================
// MAIN REQUEST HANDLER
// ============================================

try {
    // Validate session
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Nieautoryzowany dostęp', 401);
    }

    $userId = (int)$_SESSION['user_id'];

    // Route based on request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $vehicles = getUserVehicles($pdo, $userId);
                sendResponse(true, $vehicles, 'Pobrano pojazdy');
            }
            break;

        case 'POST':
            // Validate CSRF token for state-changing operations
            if (!validateCsrfToken()) {
                sendResponse(false, null, 'Nieprawidłowy token CSRF', 403);
            }

            if ($action === 'add') {
                $vehicle = addVehicle($pdo, $userId, $_POST);
                sendResponse(true, $vehicle, 'Pojazd został zapisany', 201);
            }
            break;

        case 'DELETE':
            // For DELETE requests, parse JSON body or use query param
            $vehicleId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

            if (!$vehicleId) {
                sendResponse(false, null, 'Nieprawidłowe ID pojazdu', 400);
            }

            $deleted = deleteVehicle($pdo, $userId, $vehicleId);

            if ($deleted) {
                sendResponse(true, null, 'Pojazd został usunięty');
            } else {
                sendResponse(false, null, 'Nie znaleziono pojazdu', 404);
            }
            break;

        default:
            sendResponse(false, null, 'Nieobsługiwana metoda HTTP', 405);
    }

    // If no action matched
    sendResponse(false, null, 'Nieznana akcja', 400);

} catch (Exception $e) {
    error_log("Unhandled exception in vehicle_api.php: " . $e->getMessage());
    sendResponse(false, null, 'Wystąpił błąd serwera', 500);
}
