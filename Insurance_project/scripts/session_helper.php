<?php
/**
 * Redis Session Helper
 * Zarządza rozszerzonymi danymi sesji w Redis
 *
 * Zapisuje:
 * - Email użytkownika
 * - Rolę (admin/user)
 * - Ostatni odwiedzony URL
 * - Czas logowania
 * - Czas ostatniej aktywności
 */

class SessionHelper {
    private $redis;
    private $redisPassword;
    private $redisHost;
    private $redisPort;

    public function __construct() {
        $this->redisHost = getenv('REDIS_HOST') ?: 'redis';
        $this->redisPort = getenv('REDIS_PORT') ?: 6379;
        $this->redisPassword = getenv('REDIS_PASSWORD') ?: 'changeme_redis_password_here';
    }

    /**
     * Połącz z Redis
     */
    private function connect() {
        if ($this->redis !== null) {
            return true;
        }

        try {
            $this->redis = new Redis();
            $this->redis->connect($this->redisHost, $this->redisPort);

            if ($this->redisPassword) {
                $this->redis->auth($this->redisPassword);
            }

            return true;
        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Zapisz dane użytkownika do Redis po zalogowaniu
     *
     * @param string $email
     * @param bool $isAdmin
     * @param string $redirectUrl URL gdzie użytkownik ma być przekierowany
     */
    public function saveUserSession($email, $isAdmin, $redirectUrl = null) {
        if (!$this->connect()) {
            return false;
        }

        $sessionId = session_id();
        if (empty($sessionId)) {
            error_log("Session ID is empty in saveUserSession");
            return false;
        }

        // Klucz dla rozszerzonych danych sesji
        $userDataKey = "USER_SESSION:" . $sessionId;

        $userData = [
            'email' => $email,
            'is_admin' => $isAdmin,
            'login_time' => time(),
            'last_activity' => time(),
            'last_url' => $redirectUrl ?: ($isAdmin ? '/scripts/admin.php' : '/html/main.html')
        ];

        try {
            // Zapisz jako JSON z TTL 1 godzina (3600 sekund)
            $this->redis->setex($userDataKey, 3600, json_encode($userData));
            return true;
        } catch (Exception $e) {
            error_log("Failed to save user session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobierz dane użytkownika z Redis
     *
     * @return array|null
     */
    public function getUserSession() {
        if (!$this->connect()) {
            return null;
        }

        $sessionId = session_id();
        if (empty($sessionId)) {
            return null;
        }

        $userDataKey = "USER_SESSION:" . $sessionId;

        try {
            $data = $this->redis->get($userDataKey);
            if ($data === false) {
                return null;
            }

            return json_decode($data, true);
        } catch (Exception $e) {
            error_log("Failed to get user session: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sprawdź czy użytkownik ma aktywną sesję
     *
     * @return bool
     */
    public function isSessionActive() {
        $userData = $this->getUserSession();
        return $userData !== null;
    }

    /**
     * Aktualizuj ostatni URL i czas aktywności
     *
     * @param string $url
     */
    public function updateLastUrl($url) {
        if (!$this->connect()) {
            return false;
        }

        $sessionId = session_id();
        if (empty($sessionId)) {
            return false;
        }

        $userDataKey = "USER_SESSION:" . $sessionId;

        try {
            $data = $this->redis->get($userDataKey);
            if ($data === false) {
                return false;
            }

            $userData = json_decode($data, true);
            $userData['last_url'] = $url;
            $userData['last_activity'] = time();

            // Przedłuż TTL o kolejną godzinę przy aktywności
            $this->redis->setex($userDataKey, 3600, json_encode($userData));
            return true;
        } catch (Exception $e) {
            error_log("Failed to update last URL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobierz ostatni URL użytkownika
     *
     * @return string|null
     */
    public function getLastUrl() {
        $userData = $this->getUserSession();
        return $userData['last_url'] ?? null;
    }

    /**
     * Usuń sesję użytkownika (wylogowanie)
     */
    public function destroyUserSession() {
        if (!$this->connect()) {
            return false;
        }

        $sessionId = session_id();
        if (empty($sessionId)) {
            return false;
        }

        $userDataKey = "USER_SESSION:" . $sessionId;

        try {
            $this->redis->del($userDataKey);
            return true;
        } catch (Exception $e) {
            error_log("Failed to destroy user session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobierz wszystkie aktywne sesje (dla admina)
     *
     * @return array
     */
    public function getAllActiveSessions() {
        if (!$this->connect()) {
            return [];
        }

        try {
            $keys = $this->redis->keys("USER_SESSION:*");
            $sessions = [];

            foreach ($keys as $key) {
                $data = $this->redis->get($key);
                if ($data !== false) {
                    $sessionData = json_decode($data, true);
                    $sessionData['session_id'] = str_replace('USER_SESSION:', '', $key);
                    $sessions[] = $sessionData;
                }
            }

            return $sessions;
        } catch (Exception $e) {
            error_log("Failed to get all sessions: " . $e->getMessage());
            return [];
        }
    }
}
