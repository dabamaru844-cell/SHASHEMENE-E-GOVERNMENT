<?php

/**
 * Test script to verify app.php configuration loads correctly
 */

// Load the configuration
$config = require __DIR__ . '/config/app.php';

// Verify JWT configuration
echo "Testing JWT Configuration:\n";
echo "========================\n";
if (isset($config['jwt'])) {
    echo "✓ JWT configuration exists\n";
    echo "  - Secret length: " . strlen($config['jwt']['secret']) . " characters\n";
    echo "  - Algorithm: " . $config['jwt']['algorithm'] . "\n";
    echo "  - Expiration: " . $config['jwt']['expiration'] . " seconds\n";
    echo "  - Issuer: " . $config['jwt']['issuer'] . "\n";
} else {
    echo "✗ JWT configuration missing\n";
}

echo "\nTesting API Configuration:\n";
echo "==========================\n";
if (isset($config['api'])) {
    echo "✓ API configuration exists\n";
    echo "  - CORS origins: " . count($config['api']['cors_origins']) . " configured\n";
    echo "    " . implode("\n    ", $config['api']['cors_origins']) . "\n";
    echo "  - CORS methods: " . implode(", ", $config['api']['cors_methods']) . "\n";
    echo "  - CORS headers: " . implode(", ", $config['api']['cors_headers']) . "\n";
    echo "  - CORS credentials: " . ($config['api']['cors_credentials'] ? 'true' : 'false') . "\n";
    echo "  - Rate limiting enabled: " . ($config['api']['rate_limiting']['enabled'] ? 'true' : 'false') . "\n";
    echo "  - Requests per minute: " . $config['api']['rate_limiting']['requests_per_minute'] . "\n";
    echo "  - Login attempts per hour: " . $config['api']['rate_limiting']['login_attempts_per_hour'] . "\n";
} else {
    echo "✗ API configuration missing\n";
}

echo "\n✓ Configuration file loaded successfully!\n";
