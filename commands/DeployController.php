<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Deploy command for FTP upload
 */
class DeployController extends Controller
{
    /**
     * @var bool Run migrations after deploy
     */
    public $migrate = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ['migrate'];
    }

    /**
     * Deploy the application via FTP
     */
    public function actionIndex()
    {
        $ftp = require __DIR__ . '/../config/ftp.php';

        if (empty($ftp['server']) || empty($ftp['username']) || empty($ftp['password'])) {
            $this->stderr("FTP credentials not configured in config/ftp.php\n");
            return ExitCode::CONFIG;
        }

        $this->stdout("Installing dependencies...\n");
        system('composer install --no-dev --optimize-autoloader', $code);
        if ($code !== 0) {
            $this->stderr("Failed to install dependencies\n");
            return ExitCode::SOFTWARE;
        }

        if (!function_exists('ftp_connect')) {
            $this->stderr("FTP extension is not loaded in PHP. Please enable the FTP extension in your php.ini file.\n");
            return ExitCode::SOFTWARE;
        }

        $this->stdout("Connecting to FTP...\n");
        $conn = ftp_connect($ftp['server'], $ftp['port']);
        if (!$conn) {
            $this->stderr("Failed to connect to FTP server\n");
            return ExitCode::UNAVAILABLE;
        }

        $login = ftp_login($conn, $ftp['username'], $ftp['password']);
        if (!$login) {
            $this->stderr("FTP login failed\n");
            ftp_close($conn);
            return ExitCode::UNAVAILABLE;
        }

        ftp_pasv($conn, true);

        $this->stdout("Uploading files...\n");
        $local_dir = dirname(__DIR__); // basePath
        $use_whitelist = $ftp['use_whitelist'] ?? false;
        $inclusions = $ftp['local_inclusions'] ?? [];
        $exclusions = $ftp['local_exclusions'] ?? [];
        $this->ftpUploadRecursive($conn, $local_dir, $ftp['remote_dir'], $use_whitelist, $inclusions, $exclusions);

        ftp_close($conn);

        if ($this->migrate) {
            $this->stdout("Running migrations...\n");
            system('php yiic.php migrate/up --interactive=0', $migrateCode);
            if ($migrateCode === 0) {
                $this->stdout("Migrations completed\n");
            } else {
                $this->stderr("Migrations failed\n");
            }
        }

        $this->stdout("Deploy completed\n");
        return ExitCode::OK;
    }

    private function ftpUploadRecursive($conn, $local_dir, $remote_dir_base, $use_whitelist, $inclusions, $exclusions)
    {
        $iterator = new \RecursiveDirectoryIterator($local_dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $recursive = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($recursive as $file) {
            $relativePath = substr($file->getPathname(), strlen($local_dir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize to Unix-style paths

            $shouldInclude = $use_whitelist ? $this->isIncluded($relativePath, $inclusions) : !$this->isExcluded($relativePath, $exclusions);
            if (!$shouldInclude) {
                continue;
            }

            $remotePath = $remote_dir_base . '/' . $relativePath;

            if ($file->isDir()) {
                // Create remote directory if it doesn't exist
                if (!@ftp_chdir($conn, $remotePath)) {
                    ftp_mkdir($conn, $remotePath);
                }
            } else {
                // Upload file
                ftp_put($conn, $remotePath, $file->getPathname(), FTP_BINARY);
                $this->stdout("Uploaded: $relativePath\n");
            }
        }
    }

    private function isIncluded($path, $inclusions)
    {
        foreach ($inclusions as $inc) {
            if ($path === $inc || str_starts_with($path, $inc . '/')) {
                return true;
            }
        }
        return false;
    }

    private function isExcluded($path, $exclusions)
    {
        foreach ($exclusions as $exclusion) {
            // Exact match or starts with exclusion
            if ($path === $exclusion || str_starts_with($path, $exclusion . '/')) {
                return true;
            }
            // Exclusion in middle or end
            if (strpos($path, '/' . $exclusion . '/') !== false || str_ends_with($path, '/' . $exclusion)) {
                return true;
            }
        }
        return false;
    }
}
