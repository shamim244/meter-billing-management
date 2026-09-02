<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class DatabaseDumpService
{
    /**
     * Dump the configured database directly to a gzipped file path.
     *
     * @param string $outputPath Absolute path to output .sql.gz file
     * @return array Metadata about tables and rows dumped
     */
    public function dump(string $outputPath): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql' && $this->isMysqldumpAvailable()) {
            return $this->dumpMysqlNative($outputPath);
        }

        if ($driver === 'sqlite') {
            return $this->dumpSqlite($outputPath);
        }

        // Generic robust PDO streaming chunked dumper (works for MySQL/PgSQL/SQLite without external binaries)
        return $this->dumpViaPdoStreaming($outputPath);
    }

    /**
     * Check if mysqldump command-line binary is available on the host OS.
     */
    public function isMysqldumpAvailable(): bool
    {
        try {
            $process = Process::fromShellCommandline('mysqldump --version');
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Dump MySQL database using native mysqldump with non-blocking single-transaction mode.
     */
    protected function dumpMysqlNative(string $outputPath): array
    {
        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '3306');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password", '');

        $tempSql = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dump_' . uniqid() . '.sql';

        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables --routines --triggers %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password !== '' ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($tempSql)
        );

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($tempSql)) {
            // Fallback to streaming PDO if mysqldump command failed
            return $this->dumpViaPdoStreaming($outputPath);
        }

        // Compress temp SQL file to output .sql.gz
        $this->compressFileToGzip($tempSql, $outputPath);
        @unlink($tempSql);

        return $this->collectTableSummary();
    }

    /**
     * Dump SQLite database by safely reading schemas and records.
     */
    protected function dumpSqlite(string $outputPath): array
    {
        return $this->dumpViaPdoStreaming($outputPath);
    }

    /**
     * High-efficiency PDO streaming chunked dumper that writes directly to a gzip stream.
     * Memory usage is constant (O(1)) regardless of database size.
     */
    public function dumpViaPdoStreaming(string $outputPath): array
    {
        $gz = gzopen($outputPath, 'w9');
        if (! $gz) {
            throw new \RuntimeException("Unable to open gzip output path for writing: {$outputPath}");
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $databaseName = config("database.connections.{$connection}.database");

        $header = "-- NBPDCL SaaS Pro Database Backup\n";
        $header .= "-- Generated at: " . date('Y-m-d H:i:s T') . "\n";
        $header .= "-- Database: {$databaseName} ({$driver})\n";
        $header .= "-- ------------------------------------------------------\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        gzwrite($gz, $header);

        $tables = $this->getAllTableNames();
        $summary = [];

        foreach ($tables as $table) {
            // Write Drop & Create Table DDL
            $ddl = $this->getTableCreateSql($table, $driver);
            gzwrite($gz, "\n-- Table structure for `{$table}`\n");
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");
            gzwrite($gz, $ddl . ";\n\n");

            // Stream Table Rows in chunks of 1,000
            gzwrite($gz, "-- Dumping data for `{$table}`\n");
            $rowCount = 0;

            DB::table($table)->orderBy(DB::raw('1'))->chunk(1000, function ($rows) use ($gz, $table, &$rowCount) {
                if ($rows->isEmpty()) {
                    return;
                }

                $columns = array_keys((array) $rows->first());
                $columnList = implode('`, `', $columns);

                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array) $row as $val) {
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($val) && ! is_string($val)) {
                            $values[] = $val;
                        } else {
                            $escaped = str_replace(
                                ["\\", "\0", "\n", "\r", "'", "\x1a"],
                                ["\\\\", "\\0", "\\n", "\\r", "''", "\\Z"],
                                (string) $val
                            );
                            $values[] = "'" . $escaped . "'";
                        }
                    }

                    $insertSql = "INSERT INTO `{$table}` (`{$columnList}`) VALUES (" . implode(', ', $values) . ");\n";
                    gzwrite($gz, $insertSql);
                    $rowCount++;
                }
            });

            gzwrite($gz, "\n");
            $summary[$table] = $rowCount;
        }

        gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n-- Backup Completed\n");
        gzclose($gz);

        return $summary;
    }

    /**
     * Get list of all table names in the current database.
     */
    public function getAllTableNames(): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $results = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_map(fn($r) => $r->name, $results);
        }

        if ($driver === 'mysql') {
            $database = config("database.connections.{$connection}.database");
            $results = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$database]);
            return array_map(fn($r) => $r->table_name ?? $r->TABLE_NAME, $results);
        }

        return Schema::getTableListing();
    }

    /**
     * Get the CREATE TABLE SQL definition for a table.
     */
    protected function getTableCreateSql(string $table, string $driver): string
    {
        if ($driver === 'sqlite') {
            $res = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
            return $res ? $res->sql : "CREATE TABLE `{$table}` (id INTEGER PRIMARY KEY)";
        }

        if ($driver === 'mysql') {
            $res = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            if ($res) {
                $array = (array) $res;
                return $array['Create Table'] ?? array_values($array)[1] ?? '';
            }
        }

        return "CREATE TABLE IF NOT EXISTS `{$table}`";
    }

    /**
     * Collect summary of table counts.
     */
    protected function collectTableSummary(): array
    {
        $summary = [];
        foreach ($this->getAllTableNames() as $table) {
            try {
                $summary[$table] = DB::table($table)->count();
            } catch (\Throwable $e) {
                $summary[$table] = 0;
            }
        }
        return $summary;
    }

    /**
     * Compress plain text file into GZIP format with streaming buffer.
     */
    protected function compressFileToGzip(string $sourceFile, string $destFile): void
    {
        $fp = fopen($sourceFile, 'rb');
        $gz = gzopen($destFile, 'wb9');

        while (! feof($fp)) {
            gzwrite($gz, fread($fp, 1024 * 512)); // 512KB buffer
        }

        fclose($fp);
        gzclose($gz);
    }
}
