<?php
declare(strict_types=1);

final class Env
{
    private const ENV_FILE = __DIR__ . '/../.env';

    private static array $values = [];

    private function __construct() {}

    private static function load(): void
    {
        if (self::$values !== [] || !is_file(self::ENV_FILE) || !is_readable(self::ENV_FILE)) {
            return;
        }

        $lines = file(self::ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key   = trim($key);
            $value = trim(trim($value), "\"'");

            if ($key !== '') {
                self::$values[$key] = $value;
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        self::load();
        return self::$values[$key] ?? $default;
    }
}
