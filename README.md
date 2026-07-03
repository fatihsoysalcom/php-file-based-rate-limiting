# PHP File Based Rate Limiting

This PHP script demonstrates a basic file-based rate limiting mechanism. It tracks requests per client within a defined time window, preventing abuse by returning a 429 Too Many Requests error when the limit is exceeded. The example uses a simple JSON file for persistence, mimicking how a real application might use a cache or database for rate limit storage.

## Language

`php`

## How to Run

1. Save the code as `app.php`.
2. **From CLI:** Run `php app.php` multiple times to observe the rate limit.
3. **From Web Server:** Place `app.php` in your web server's document root and access it via browser (e.g., `http://localhost/app.php`). Refresh the page multiple times.

## Original Article

This example accompanies the Turkish article: [API Kötüye Kullanımını Durdurmak: Laravel'de Gelişmiş Hız Sınırlama (Rate Limiting) Teknikleri](https://fatihsoysal.com/blog/api-kotuye-kullanimini-durdurmak-laravelde-gelismis-hiz-sinirlama-rate-limiting-teknikleri/).

## License

MIT — see [LICENSE](LICENSE).
