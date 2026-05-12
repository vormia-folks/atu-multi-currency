<?php

namespace Vormia\ATUMultiCurrency\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Host-app touches for ATU Multi-Currency: optional .env keys only.
 * Migrations, routes, config merge, controllers, and Livewire views load from the package (no file copy).
 */
class Installer
{
    private const ENV_KEYS = [
        'ATU_CURRENCY_API_KEY' => '',
        'ATU_CURRENCY_UPDATE_FREQUENCY' => 'daily',
        'ATU_CURRENCY_SETTINGS_SOURCE' => 'database',
    ];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $appBasePath
    ) {}

    /**
     * @return array{env: array}
     */
    public function install(bool $touchEnv = true): array
    {
        $envChanges = $touchEnv ? $this->ensureEnvKeys() : [];

        return ['env' => $envChanges];
    }

    /**
     * @return array{env: array}
     */
    public function uninstall(bool $touchEnv = true): array
    {
        $env = $touchEnv ? $this->removeEnvKeys() : [];

        return ['env' => $env];
    }

    private function pathJoin(string ...$parts): string
    {
        $filtered = collect($parts)->filter(fn ($p) => $p !== '');

        if ($filtered->isEmpty()) {
            return '';
        }

        $first = $filtered->first();
        $isAbsolute = str_starts_with($first, '/') || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:/i', $first));

        if ($isAbsolute) {
            $first = rtrim($first, '/\\');
            $rest = $filtered->skip(1)
                ->map(fn ($p) => trim($p, '/\\'))
                ->filter(fn ($p) => $p !== '');

            return $rest->isEmpty()
                ? $first
                : $first . DIRECTORY_SEPARATOR . $rest->implode(DIRECTORY_SEPARATOR);
        }

        return $filtered
            ->map(fn ($p) => trim($p, '/\\'))
            ->implode(DIRECTORY_SEPARATOR);
    }

    public function ensureEnvKeys(): array
    {
        $paths = [
            $this->pathJoin($this->appBasePath, '.env'),
            $this->pathJoin($this->appBasePath, '.env.example'),
        ];

        $added = [];

        foreach ($paths as $envPath) {
            if (! $this->files->exists($envPath)) {
                $added[$envPath] = [];
                continue;
            }

            $existing = $this->files->get($envPath);
            $addedKeys = [];
            $updated = $this->appendEnvBlock($existing, $addedKeys);

            if ($updated !== $existing) {
                $this->files->put($envPath, $updated);
                $added[$envPath] = $addedKeys;
            } else {
                $added[$envPath] = [];
            }
        }

        return $added;
    }

    private function appendEnvBlock(string $current, ?array &$addedKeys = []): string
    {
        $addedKeys = [];
        $lines = rtrim($current) === '' ? [] : preg_split('/\r\n|\r|\n/', $current);
        $presentKeys = $this->extractExistingKeys($lines);

        foreach (self::ENV_KEYS as $key => $value) {
            if (! in_array($key, $presentKeys, true)) {
                $addedKeys[] = $key;
            }
        }

        if ($addedKeys === []) {
            return $current;
        }

        $block = [];
        $block[] = '# ATU Multi-Currency Configuration';
        foreach ($addedKeys as $key) {
            $block[] = $key . '=' . self::ENV_KEYS[$key];
        }

        $merged = array_merge($lines, $lines ? [''] : [], $block);

        return implode(PHP_EOL, $merged) . PHP_EOL;
    }

    private function extractExistingKeys(array $lines): array
    {
        $keys = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key] = explode('=', $line, 2);
            $keys[] = trim($key);
        }

        return $keys;
    }

    public function removeEnvKeys(): array
    {
        $paths = [
            $this->pathJoin($this->appBasePath, '.env'),
            $this->pathJoin($this->appBasePath, '.env.example'),
        ];

        $removed = [];

        foreach ($paths as $envPath) {
            $removedKeys = [];
            if (! $this->files->exists($envPath)) {
                $removed[$envPath] = [];
                continue;
            }

            $content = $this->files->get($envPath);
            $updated = $this->stripEnvKeys($content, $removedKeys);

            if ($updated !== $content) {
                $this->files->put($envPath, $updated);
            }

            $removed[$envPath] = $removedKeys;
        }

        return $removed;
    }

    private function stripEnvKeys(string $content, ?array &$removedKeys = []): string
    {
        $removedKeys = [];
        $lines = rtrim($content) === '' ? [] : preg_split('/\r\n|\r|\n/', $content);
        $remaining = [];

        foreach ($lines as $line) {
            if (str_contains($line, '# ATU Multi-Currency Configuration')) {
                continue;
            }

            $trimmedLine = trim($line);
            if (str_starts_with($trimmedLine, '#')) {
                $remaining[] = $line;
                continue;
            }

            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $key = trim($key);

                if (array_key_exists($key, self::ENV_KEYS)) {
                    $removedKeys[] = $key;
                    continue;
                }
            }

            $remaining[] = $line;
        }

        $normalized = preg_replace("/[\r\n]{3,}/", "\n\n", implode(PHP_EOL, $remaining));

        return rtrim($normalized) . PHP_EOL;
    }
}
