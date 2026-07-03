# php-file-based-rate-limiting
This PHP script demonstrates a basic file-based rate limiting mechanism. It tracks requests per client within a defined time window, preventing abuse by returning a 429 Too Many Requests error when the limit is exceeded. The example uses a simple JSON file for persistence, mimicking how a real application might use a cache or database for rate limi
