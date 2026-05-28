<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class GenerateCommandRepositoryTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:repository-tests';

    protected $description = 'Genera test unitarios para todas las interfaces de repositorio del dominio';
    private array $repositoryTypes = ['Command', 'Query', 'Stripe', 'Cache'];

    public function handle()
    {
        foreach ($this->repositoryTypes as $type) {
            $this->generateTestsForType($type);
        }

        $this->info("✓ Todos los tests de repositorio generados exitosamente!");
        return 0;
    }

    /**
     * Generate tests for a specific repository type
     */
    private function generateTestsForType(string $type): void
    {
        $interfacesPath = app_path("Core/Domain/Repositories/{$type}");

        if (!File::exists($interfacesPath)) {
            $this->warn("⚠ No se encontró el directorio para {$type}: {$interfacesPath}");
            return;
        }

        $interfaces = $this->findInterfaces($interfacesPath, $type);

        foreach ($interfaces as $interface) {
            $this->generateRepositoryTest($interface, $type);
        }
    }

    /**
     * Find all interface files recursively for a specific type
     */
    private function findInterfaces(string $path, string $type): array
    {
        $interfaces = [];

        if (!File::exists($path)) {
            return $interfaces;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $filename = $file->getFilename();

            // Skip non-PHP files
            if (!str_ends_with($filename, '.php')) {
                continue;
            }

            // Skip abstract classes and base classes
            if (str_starts_with($filename, 'Base') ||
                str_starts_with($filename, 'Abstract') ||
                str_starts_with($filename, 'I')) {
                continue;
            }

            // Get full class name
            $relativePath = str_replace(
                [app_path(), '.php', '/'],
                ['App', '', '\\'],
                $file->getPathname()
            );

            $className = str_replace('.php', '', $relativePath);

            // Check if it's an interface
            try {
                $reflection = new ReflectionClass($className);
                if ($reflection->isInterface()) {
                    $interfaces[] = $className;
                }
            } catch (\Throwable $e) {
                $this->warn("⚠ No se pudo cargar la clase {$className}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info("✓ Encontradas " . count($interfaces) . " interfaces en {$type}");
        return $interfaces;
    }

    /**
     * Generate test for a repository interface
     */
    private function generateRepositoryTest(string $interfaceClass, string $type): void
    {
        try {
            $reflection = new ReflectionClass($interfaceClass);
            $interfaceName = $reflection->getShortName();

            // Extraer el namespace específico después del tipo
            $baseNamespace = "App\\Core\\Domain\\Repositories\\{$type}\\";
            $relativeNamespace = str_replace($baseNamespace, '', $interfaceClass);

            // Quitar el nombre de la interfaz del final
            $subNamespace = dirname($relativeNamespace);
            if ($subNamespace === '.') {
                $subNamespace = '';
            }

            // Determinar nombre de la entidad
            $entityName = $this->extractEntityName($interfaceName, $type);

            // Generar archivo de test
            $this->generateTestFile($interfaceClass, $interfaceName, $entityName, $type, $subNamespace);

        } catch (\Throwable $e) {
            $this->error("✗ Error generando test para {$interfaceClass}: {$e->getMessage()}");
        }
    }

    /**
     * Extract entity name from interface name
     */
    private function extractEntityName(string $interfaceName, string $type): string
    {
        // Primero, quitar sufijos comunes de interfaces
        $entityName = $interfaceName;

        // Lista de sufijos a remover
        $suffixes = [
            'RepInterface',
            'RepositoryInterface',
            'Interface',
            'Repo',
            'Repository',
            'Gateway',
            'Service'
        ];

        foreach ($suffixes as $suffix) {
            if (str_ends_with($entityName, $suffix)) {
                $entityName = substr($entityName, 0, -strlen($suffix));
                break;
            }
        }

        // Para Stripe y Cache, pueden tener nombres específicos
        if ($type === 'Stripe') {
            // Ej: StripePaymentGateway -> Payment
            $entityName = preg_replace('/^Stripe/', '', $entityName);
        }

        if ($type === 'Cache') {
            // Ej: CacheUserService -> User
            $entityName = preg_replace('/^Cache/', '', $entityName);
        }

        // Si después de todo queda vacío, usar el nombre original
        if (empty($entityName)) {
            $entityName = $interfaceName;
        }

        return $entityName;
    }

    /**
     * Generate the actual test file
     */
    private function generateTestFile(
        string $interfaceClass,
        string $interfaceName,
        string $entityName,
        string $type,
        string $subNamespace
    ): void {
        $testName = "{$interfaceName}Test";

        // Construir ruta del test
        $testPath = base_path("tests/Unit/Domain/Repositories/{$type}");

        if (!empty($subNamespace)) {
            // Convertir namespace a estructura de directorios
            $dirPath = str_replace('\\', '/', $subNamespace);
            $testPath .= '/' . $dirPath;
        }

        $testPath .= "/{$testName}.php";

        // Verificar si el test ya existe
        if (File::exists($testPath)) {
            $this->warn("⚠ Test para {$interfaceName} ya existe: {$testPath}");
            return;
        }

        // Leer la plantilla
        $stubPath = base_path('stubs/repository-test.stub');
        if (!File::exists($stubPath)) {
            $this->error("✗ No se encontró la plantilla: {$stubPath}");
            return;
        }

        $stub = File::get($stubPath);

        // Preparar reemplazos
        $replacements = [
            '{{RepositoryType}}' => $type,
            '{{Namespace}}' => !empty($subNamespace) ? $subNamespace : $type,
            '{{InterfaceClass}}' => $interfaceClass,
            '{{RepositoryName}}' => $interfaceName,
            '{{EntityName}}' => $entityName,
        ];

        // Reemplazar marcadores
        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        // Asegurar que el directorio existe y escribir el archivo
        File::ensureDirectoryExists(dirname($testPath));
        File::put($testPath, $content);

        $this->info("✓ Test creado: {$testPath}");
    }

    /**
     * Helper para debug: mostrar estructura encontrada
     */
    private function debugStructure(string $path): void
    {
        $this->line("🔍 Examinando: {$path}");

        if (!File::exists($path)) {
            $this->warn("  Directorio no existe");
            return;
        }

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $this->line("  📄 " . $file->getRelativePathname());
        }
    }
}
