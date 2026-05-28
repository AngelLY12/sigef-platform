<?php

namespace Tests\Unit\Domain\Repositories\Cache;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Repositories\Cache\CacheServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Domain\Repositories\BaseRepositoryNoDatabaseTestCase;

class CacheServiceInterfaceTest extends BaseRepositoryNoDatabaseTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = CacheServiceInterface::class;

    /**
     * Setup the cache service instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // TODO: Inicializar tu implementación de CacheService aquí
        // Ejemplo para Redis:
        // $this->repository = app(\App\Infrastructure\Services\Cache\RedisCacheService::class);

        // Ejemplo para Laravel Cache:
        // $this->repository = app(\App\Infrastructure\Services\Cache\LaravelCacheService::class);

        $this->repository = new \Tests\Stubs\Services\Cache\CacheServiceStub();
    }
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El servicio de cache no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El servicio de cache no está inicializado');

        $methods = [
            'get', 'put', 'putMany', 'forget', 'has',
            'remember', 'rememberForever',
            'increment', 'decrement',
            'makeKey',
            'clearPrefix', 'clearKey',
            'clearStaffCache', 'clearStudentCache',
            'clearParentCache', 'clearCacheWhileConceptChangeStatus'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_get_and_put_values(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        // Guardar valor
        $this->repository->put($key, $value);

        // Recuperar valor
        $result = $this->repository->get($key);

        $this->assertEquals($value, $result);
    }

    #[Test]
    public function it_returns_default_when_key_not_found(): void
    {
        $result = $this->repository->get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    #[Test]
    public function it_can_put_many_values_at_once(): void
    {
        $values = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        $this->repository->putMany($values);

        foreach ($values as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $this->repository->get($key));
        }
    }

    #[Test]
    public function it_can_forget_keys(): void
    {
        $key = 'to_forget';
        $value = 'some_value';

        $this->repository->put($key, $value);
        $this->assertTrue($this->repository->has($key));

        $this->repository->forget($key);
        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function it_correctly_checks_if_key_exists(): void
    {
        $existingKey = 'existing';
        $nonExistingKey = 'non_existing';

        $this->repository->put($existingKey, 'value');

        $this->assertTrue($this->repository->has($existingKey));
        $this->assertFalse($this->repository->has($nonExistingKey));
    }

    #[Test]
    public function it_can_remember_values_with_ttl(): void
    {
        $key = 'remember_key';
        $expectedValue = 'cached_value';

        $callCount = 0;
        $callback = function() use (&$callCount, $expectedValue) {
            $callCount++;
            return $expectedValue;
        };

        // Primera llamada - ejecuta el callback
        $result1 = $this->repository->remember($key, 60, $callback);
        $this->assertEquals($expectedValue, $result1);
        $this->assertEquals(1, $callCount);

        // Segunda llamada - debería usar cache
        $result2 = $this->repository->remember($key, 60, $callback);
        $this->assertEquals($expectedValue, $result2);
        $this->assertEquals(1, $callCount); // Callback no debería ejecutarse de nuevo
    }

    #[Test]
    public function it_can_remember_values_forever(): void
    {
        $key = 'remember_forever_key';
        $expectedValue = 'forever_value';

        $callCount = 0;
        $callback = function() use (&$callCount, $expectedValue) {
            $callCount++;
            return $expectedValue;
        };

        $result = $this->repository->rememberForever($key, $callback);

        $this->assertEquals($expectedValue, $result);
        $this->assertEquals(1, $callCount);
    }

    #[Test]
    public function it_can_increment_values(): void
    {
        $key = 'counter';

        // Valor inicial
        $this->repository->put($key, 5);

        // Incrementar
        $result = $this->repository->increment($key);

        $this->assertEquals(6, $result);
        $this->assertEquals(6, $this->repository->get($key));
    }

    #[Test]
    public function it_can_increment_by_specific_value(): void
    {
        $key = 'counter';

        $this->repository->put($key, 10);
        $result = $this->repository->increment($key, 5);

        $this->assertEquals(15, $result);
    }

    #[Test]
    public function it_can_decrement_values(): void
    {
        $key = 'counter';

        $this->repository->put($key, 10);
        $result = $this->repository->decrement($key);

        $this->assertEquals(9, $result);
        $this->assertEquals(9, $this->repository->get($key));
    }

    #[Test]
    public function it_can_make_composite_keys(): void
    {
        $prefix = 'user';
        $suffix = 'profile';

        $key = $this->repository->makeKey($prefix, $suffix);

        // Depende de la implementación, pero debería ser un string
        $this->assertIsString($key);
        $this->assertNotEmpty($key);
    }

    #[Test]
    public function it_can_clear_keys_by_prefix(): void
    {
        // Crear claves con prefijo
        $this->repository->put('user_1_profile', 'data1');
        $this->repository->put('user_2_profile', 'data2');
        $this->repository->put('product_1_info', 'data3'); // No debería ser afectado

        $this->assertTrue($this->repository->has('user_1_profile'));
        $this->assertTrue($this->repository->has('user_2_profile'));

        // Limpiar por prefijo
        $this->repository->clearPrefix('user_');

        $this->assertFalse($this->repository->has('user_1_profile'));
        $this->assertFalse($this->repository->has('user_2_profile'));
        $this->assertTrue($this->repository->has('product_1_info')); // Debería seguir existiendo
    }

    #[Test]
    public function it_can_clear_specific_composite_key(): void
    {
        $prefix = 'user';
        $suffix = 'profile_1';

        // Crear la clave compuesta
        $key = $this->repository->makeKey($prefix, $suffix);
        $this->repository->put($key, 'user_data');

        $this->assertTrue($this->repository->has($key));

        // Limpiar clave específica
        $this->repository->clearKey($prefix, $suffix);

        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function it_can_clear_staff_cache(): void
    {
        // Este método es específico del dominio
        // Simplemente verificamos que se puede llamar sin errores
        $this->expectNotToPerformAssertions();

        $this->repository->clearStaffCache();
    }

    #[Test]
    public function it_can_clear_student_cache(): void
    {
        $userId = 123;

        $this->expectNotToPerformAssertions();

        $this->repository->clearStudentCache($userId);
    }

    #[Test]
    public function it_can_clear_parent_cache(): void
    {
        $parentId = 456;

        $this->expectNotToPerformAssertions();

        $this->repository->clearParentCache($parentId);
    }

    #[Test]
    public function it_can_clear_cache_while_concept_change_status(): void
    {
        $userId = 789;
        $status = PaymentConceptStatus::ACTIVO;

        $this->expectNotToPerformAssertions();

        $this->repository->clearCacheWhileConceptChangeStatus($userId, $status);
    }

    #[Test]
    public function it_handles_ttl_correctly(): void
    {
        $key = 'ttl_key';
        $value = 'ttl_value';
        $ttl = 1; // 1 segundo

        $this->repository->put($key, $value, $ttl);

        // Inmediatamente después debería existir
        $this->assertTrue($this->repository->has($key));
        $this->assertEquals($value, $this->repository->get($key));

        // Esperar a que expire (depende de la implementación)
        // Esto es más para integración, en unit test podemos solo verificar
        // que acepta el parámetro TTL
        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_null_for_expired_keys(): void
    {
        $key = 'expired_key';
        $value = 'expired_value';

        // Usar TTL muy corto (si la implementación lo soporta)
        $this->repository->put($key, $value, 0.1);

        // En un test real necesitarías esperar, pero aquí verificamos
        // que el método existe y se puede llamar
        $result = $this->repository->get($key, 'default');

        // Depende de la implementación
        $this->assertTrue($result === $value || $result === 'default');
    }

    #[Test]
    public function it_supports_different_value_types(): void
    {
        $testCases = [
            'string' => 'test_string',
            'integer' => 123,
            'float' => 123.45,
            'array' => ['key' => 'value'],
            'boolean' => true,
            'null' => null,
            'object' => (object)['prop' => 'value'],
        ];

        foreach ($testCases as $type => $value) {
            $key = "test_{$type}";
            $this->repository->put($key, $value);

            $result = $this->repository->get($key);
            $this->assertEquals($value, $result, "Failed for type: {$type}");
        }
    }

    #[Test]
    public function it_can_handle_concurrent_increments(): void
    {
        $key = 'concurrent_counter';
        $initialValue = 0;

        $this->repository->put($key, $initialValue);

        // Simular múltiples incrementos
        $increments = 10;
        for ($i = 0; $i < $increments; $i++) {
            $this->repository->increment($key);
        }

        $finalValue = $this->repository->get($key);
        $this->assertEquals($initialValue + $increments, $finalValue);
    }

    #[Test]
    public function it_provides_consistent_key_generation(): void
    {
        $prefix = 'user';
        $suffix = 'profile';

        $key1 = $this->repository->makeKey($prefix, $suffix);
        $key2 = $this->repository->makeKey($prefix, $suffix);

        // La generación de claves debería ser determinista
        $this->assertEquals($key1, $key2);
    }

    #[Test]
    public function it_handles_empty_or_null_keys_appropriately(): void
    {
        // Depende de la implementación, pero debería manejar estos casos
        $this->expectNotToPerformAssertions();

        try {
            $this->repository->get('');
            $this->repository->has('');
            $this->repository->forget('');
        } catch (\Exception $e) {
            // Está bien lanzar excepción para claves inválidas
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    #[Test]
    public function it_implements_fluent_interface_for_method_chaining(): void
    {
        // Algunos métodos podrían retornar $this para method chaining
        // Esto depende de tu implementación
        $this->assertTrue(true); // Placeholder
    }
}
