<?php

declare(strict_types=1);

namespace Core;

/**
 * Runner de migraciones SQL. Archivos .sql con secciones "-- @UP" y "-- @DOWN".
 * Las fuentes (core + módulos activos) las pasa el llamador: core no conoce los módulos.
 */
final class Migrator
{
    /** @param array<string, string> $sources ['core' => '/ruta/migrations', 'vet' => ...] en orden de ejecución */
    public function __construct(private readonly array $sources)
    {
    }

    public function migrate(): array
    {
        $this->ensureTable();
        $executed = $this->executedSet();
        $batch = ((int) Database::scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations')) + 1;
        $applied = [];

        foreach ($this->sources as $module => $dir) {
            foreach ($this->files($dir) as $file) {
                $name = basename($file);
                if (isset($executed["{$module}/{$name}"])) {
                    continue;
                }
                $this->runSection($file, 'up', $module);
                Database::insert('migrations', [
                    'module' => $module,
                    'filename' => $name,
                    'batch' => $batch,
                    'executed_at' => now(),
                ]);
                $applied[] = "{$module}/{$name}";
            }
        }
        return $applied;
    }

    public function rollback(int $steps = 1): array
    {
        $this->ensureTable();
        $reverted = [];
        for ($i = 0; $i < $steps; $i++) {
            $batch = Database::scalar('SELECT MAX(batch) FROM migrations');
            if ($batch === null) {
                break;
            }
            $rows = Database::select('SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC', [(int) $batch]);
            foreach ($rows as $row) {
                $dir = $this->sources[$row['module']] ?? null;
                $file = $dir !== null ? $dir . DIRECTORY_SEPARATOR . $row['filename'] : null;
                if ($file === null || !is_file($file)) {
                    throw new \RuntimeException("No se encontró la migración {$row['module']}/{$row['filename']} para revertir (¿módulo desactivado?).");
                }
                $this->runSection($file, 'down', $row['module']);
                Database::delete('migrations', 'id = ?', [$row['id']]);
                $reverted[] = "{$row['module']}/{$row['filename']}";
            }
        }
        return $reverted;
    }

    /** @return array{executed: string[], pending: string[]} */
    public function status(): array
    {
        $this->ensureTable();
        $executed = $this->executedSet();
        $pending = [];
        foreach ($this->sources as $module => $dir) {
            foreach ($this->files($dir) as $file) {
                $key = $module . '/' . basename($file);
                if (!isset($executed[$key])) {
                    $pending[] = $key;
                }
            }
        }
        return ['executed' => array_keys($executed), 'pending' => $pending];
    }

    public function pendingCount(): int
    {
        return count($this->status()['pending']);
    }

    public function ensureTable(): void
    {
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                module VARCHAR(40) NOT NULL,
                filename VARCHAR(190) NOT NULL,
                batch INT NOT NULL,
                executed_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_migrations (module, filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function executedSet(): array
    {
        $rows = Database::select('SELECT module, filename FROM migrations');
        $set = [];
        foreach ($rows as $row) {
            $set[$row['module'] . '/' . $row['filename']] = true;
        }
        return $set;
    }

    private function files(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function runSection(string $file, string $section, string $module): void
    {
        $sql = $this->section((string) file_get_contents($file), $section);
        foreach ($this->statements($sql) as $statement) {
            try {
                Database::pdo()->exec($statement);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    "Falló la migración {$module}/" . basename($file) . " ({$section}): " . $e->getMessage()
                );
            }
        }
    }

    private function section(string $content, string $which): string
    {
        $parts = preg_split('/^--\s*@DOWN\s*$/mi', $content, 2);
        $up = preg_replace('/^--\s*@UP\s*$/mi', '', $parts[0] ?? '');
        $down = $parts[1] ?? '';
        return $which === 'up' ? (string) $up : $down;
    }

    private function statements(string $sql): array
    {
        $raw = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        $statements = [];
        foreach ($raw as $statement) {
            $statement = trim($statement);
            if ($statement !== '' && !str_starts_with($statement, '--')) {
                $statements[] = $statement;
            }
        }
        return $statements;
    }
}
