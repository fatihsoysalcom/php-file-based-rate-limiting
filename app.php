<?php

// Define rate limiting constants
define('MAX_REQUESTS', 5); // Max requests allowed within the time window
define('TIME_WINDOW_SECONDS', 60); // The duration of the time window in seconds

// Storage file for rate limit data. In a real Laravel app, this would typically be Redis, Memcached, or a database.
// For this single-file example, we use a simple JSON file for persistence across requests.
define('RATE_LIMIT_STORAGE_FILE', __DIR__ . '/rate_limits.json');

/**
 * Get a unique identifier for the client making the request.
 * In a web context, this is often the client's IP address or an API key.
 * For CLI execution, we use a fixed identifier to simulate a client.
 */
function getClientId(): string
{
    if (php_sapi_name() == 'cli') {
        // For CLI, use a generic ID. To simulate different clients, you'd modify this.
        return 'simulated_cli_client';
    } else {
        // For web, use the remote IP address.
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown_client';
    }
}

/**
 * Loads rate limit data from the storage file.
 * @return array An associative array where keys are client IDs and values are arrays of request timestamps.
 */
function loadRateLimitData(): array
{
    if (!file_exists(RATE_LIMIT_STORAGE_FILE)) {
        return [];
    }
    $data = file_get_contents(RATE_LIMIT_STORAGE_FILE);
    return json_decode($data, true) ?? [];
}

/**
 * Saves rate limit data to the storage file.
 * @param array $data The rate limit data to save.
 */
function saveRateLimitData(array $data): void
{
    // Use LOCK_EX to prevent race conditions when writing to the file
    file_put_contents(RATE_LIMIT_STORAGE_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

// --- Main Rate Limiting Logic ---

$clientId = getClientId();
$rateLimits = loadRateLimitData();

$currentTime = time();
// Get current requests for this client, initializing if none exist
$clientRequests = $rateLimits[$clientId] ?? [];

// Filter out requests that are older than the defined time window.
// This ensures only relevant requests contribute to the current limit count.
$clientRequests = array_filter($clientRequests, function ($timestamp) use ($currentTime) {
    return ($currentTime - $timestamp) < TIME_WINDOW_SECONDS;
});

// Determine the timestamp of the oldest request currently in the window.
// This is used to calculate when the next slot will become available.
$firstRequestTimeInWindow = !empty($clientRequests) ? min($clientRequests) : $currentTime;

// Calculate when the rate limit window for the oldest request will reset.
$resetTime = $firstRequestTimeInWindow + TIME_WINDOW_SECONDS;
$retryAfterSeconds = max(0, $resetTime - $currentTime);

// Check if the client has exceeded the rate limit.
if (count($clientRequests) >= MAX_REQUESTS) {
    // Rate limit exceeded: send appropriate HTTP headers for web requests.
    if (php_sapi_name() != 'cli') {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: ' . $retryAfterSeconds); // Inform the client when they can retry
        header('X-RateLimit-Limit: ' . MAX_REQUESTS);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . $resetTime); // Unix timestamp
    }
    echo "Error: Too Many Requests. Please try again after " . $retryAfterSeconds . " seconds.\n";
    echo "You have made " . count($clientRequests) . " requests in the last " . TIME_WINDOW_SECONDS . " seconds.\n";
    exit(1); // Terminate script execution with an error status
}

// If the limit is not exceeded, record the current request.
$clientRequests[] = $currentTime;
$rateLimits[$clientId] = $clientRequests;

// Save the updated rate limit data back to the storage file.
saveRateLimitData($rateLimits);

// --- Simulate API Response ---
// For successful requests, provide rate limit status in headers for web requests.
if (php_sapi_name() != 'cli') {
    header('Content-Type: application/json');
    header('X-RateLimit-Limit: ' . MAX_REQUESTS);
    header('X-RateLimit-Remaining: ' . (MAX_REQUESTS - count($clientRequests)));
    header('X-RateLimit-Reset: ' . $resetTime); // Unix timestamp
}

// Output a success message with current rate limit status.
echo json_encode([
    'status' => 'success',
    'message' => 'API request processed successfully.',
    'requests_made_in_window' => count($clientRequests),
    'remaining_requests' => MAX_REQUESTS - count($clientRequests),
    'reset_in_seconds' => $retryAfterSeconds, // When the oldest request will expire, freeing a slot
    'client_id' => $clientId
], JSON_PRETTY_PRINT);

?>