<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class RestoreDatabaseUseCase
{
    public function __construct(private DBRepInterface $rep)
    {
    }

    public function execute(): bool
    {
        try {
            $check = $this->rep->checkDBStatus();
            if ($check) {
                Log::channel('stderr')->info('La base de datos está activa, no es necesario restaurar.');
                return true;
            }
        } catch (\Exception $e) {
            Log::channel('stderr')->warning('No se pudo conectar a la base de datos: ' . $e->getMessage());
            return false;
        }

        Log::channel('stderr')->warning('Restaurando la última copia de seguridad...');

        $files = collect(Storage::disk('gcs')->allFiles())
        ->filter(fn($f) => str_ends_with(strtolower($f), '.zip'))
        ->sortDesc()
        ->values();

        if ($files->isEmpty()) {
            Log::channel('stderr')->error('No hay respaldos disponibles en Google Drive.');
            return false;
        }

        $latestBackup = $files->first();
        Log::channel('stderr')->info("Descargando respaldo");

        $localPath = storage_path('app/restore.zip');
        try {
            $content = Storage::disk('gcs')->get($latestBackup);
            file_put_contents($localPath, $content);
        } catch (\Exception $e) {
            Log::channel('stderr')->error('Error al descargar el respaldo: ' . $e->getMessage());
            return false;
        }

        $restoreDir = storage_path('app/restore');
        if (!file_exists($restoreDir)) {
            mkdir($restoreDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($localPath) === true) {
            $zip->extractTo($restoreDir);
            $zip->close();
        } else {
            Log::channel('stderr')->error('No se pudo abrir el archivo ZIP');
            return false;
        }

        $directory = new RecursiveDirectoryIterator($restoreDir);
        $iterator  = new RecursiveIteratorIterator($directory);
        $sqlFiles  = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'sql') {
                $sqlFiles[] = $file->getPathname();
            }
        }

        if (empty($sqlFiles)) {
            Log::channel('stderr')->error('No se encontró un archivo SQL para restaurar.');
            return false;
        }

        $sqlFile = $sqlFiles[0];
        Log::channel('stderr')->info("Archivo SQL encontrado: {$sqlFile}");
        $database = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');

        $dropCreateCommand = "mysql -h {$host} -u {$user} -p{$password} --skip-ssl -e 'DROP DATABASE IF EXISTS {$database}; CREATE DATABASE {$database};'";
        exec($dropCreateCommand, $dropOutput, $dropReturnVar);

        if ($dropReturnVar !== 0) {
            Log::channel('stderr')->error('Error al dropear/crear la base de datos');
            Log::channel('stderr')->error('Output: ' . implode("\n", $dropOutput));
            return false;
        }

        $command = "mysql -h {$host} -u {$user} -p{$password} --skip-ssl {$database} < {$sqlFile}";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::channel('stderr')->error('Error restaurando la base de datos con MySQL nativo.');
            Log::channel('stderr')->error('Output: ' . implode("\n", $output));
            return false;
        }

        Log::channel('stderr')->info('Base de datos restaurada correctamente con MySQL nativo.');

        return true;

    }
}
