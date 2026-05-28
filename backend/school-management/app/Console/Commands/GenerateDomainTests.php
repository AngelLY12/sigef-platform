<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDomainTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:domain-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera test unitarios para todas las entidades del dominio';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $entitiesPath = app_path('core/Domain/Entities');

        if (!File::exists($entitiesPath)) {
            $this->error("No se encontró el directorio de entidades: {$entitiesPath}");
            return 1;
        }
        $entities = File::files($entitiesPath);

        foreach ($entities as $entityFile) {
            $entityName = pathinfo($entityFile->getFilename(), PATHINFO_FILENAME);

            if (str_contains($entityName, 'Base') || $entityName === 'Entity') {
                continue;
            }

            $this->generateTest($entityName);
        }

        $this->info("✓ Tests generados exitosamente!");
        return 0;
    }

    private function generateTest(string $entityName)
    {
        $testName = "{$entityName}Test";
        $testPath = base_path("tests/Unit/Domain/Entities/{$testName}.php");

        if (File::exists($testPath)) {
            $this->warn("⚠ Test para {$entityName} ya existe, omitiendo...");
            return;
        }

        $stub = File::get(base_path('stubs/domain-test.stub'));
        $content = str_replace(
            ['{{EntityName}}', '{{EntityVar}}'],
            [$entityName, lcfirst($entityName)],
            $stub
        );

        File::ensureDirectoryExists(dirname($testPath));
        File::put($testPath, $content);

        $this->info("✓ Test creado: {$testName}");
    }
}
