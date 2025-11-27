<?php
/**
 * Session Configuration Bootstrap
 * This file is automatically prepended to all PHP scripts via php.ini
 * It configures Redis session handler with proper authentication
 */

// Get Redis password from environment
$redisPassword = getenv('REDIS_PASSWORD') ?: 'changeme_redis_password_here';
$redisHost = getenv('REDIS_HOST') ?: 'redis';
$redisPort = getenv('REDIS_PORT') ?: '6379';

// Configure session save path with authentication
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://{$redisHost}:{$redisPort}?auth={$redisPassword}");

// Security settings (basic settings in php.ini, dynamic settings here)
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

// Set cookie_secure only in production (HTTPS)
if (getenv('APP_ENV') === 'production') {
    ini_set('session.cookie_secure', '1');
} else {
    ini_set('session.cookie_secure', '0');
}
