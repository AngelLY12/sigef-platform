<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\PendingPayment;

use App\Core\Application\UseCases\Payments\Student\PendingPayment\PayConceptUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Exceptions\Conflict\UserExplicitlyExcludedException;
use App\Exceptions\NotAllowed\PaymentRetryNotAllowedException;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Exceptions\Validation\ConceptExpiredException;
use App\Exceptions\Validation\ConceptInactiveException;
use App\Exceptions\Validation\ConceptNotStartedException;
use App\Exceptions\NotAllowed\UserNotAllowedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\PaymentConcept as PaymentConceptModel;
use App\Models\Payment as PaymentModel;
use App\Models\Career as CareerModel;
use App\Models\StudentDetail as StudentDetailModel;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Stripe\Checkout\Session;

class PayConceptUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private PayConceptUseCase $useCase;
    private MockInterface $stripeMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios sin usar seeder (para evitar deadlocks)
        $this->createRoles();

        // Crear mock de Stripe
        $this->stripeMock = Mockery::mock(\App\Core\Domain\Repositories\Stripe\StripeGatewayInterface::class);

        // Rebindear el servicio en el container
        $this->app->instance(\App\Core\Domain\Repositories\Stripe\StripeGatewayInterface::class, $this->stripeMock);

        $this->useCase = app(PayConceptUseCase::class);
    }

    private function createRoles(): void
    {
        // Crear roles solo si no existen
        if (!Role::where('name', UserRoles::STUDENT->value)->exists()) {
            Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'sanctum']);
        }
        if (!Role::where('name', UserRoles::APPLICANT->value)->exists()) {
            Role::create(['name' => UserRoles::APPLICANT->value, 'guard_name' => 'sanctum']);
        }
    }

    private function createStripeSession(array $data = []): Session
    {
        $sessionId = $data['id'] ?? 'cs_test_123456789012345678901234';
        $sessionData = array_merge([
            'id' => $sessionId,
            'object' => 'checkout.session',
            'payment_intent' => 'pi_' . uniqid(),
            'payment_status' => 'unpaid',
            'status' => 'open',
            'amount_total' => 50000,
            'created' => time(),
            'url' => 'https://checkout.stripe.com/c/pay/test_session',
            'metadata' => ['concept_name' => 'Test Concept'],
        ], $data);

        return Session::constructFrom($sessionData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_payment_session_for_valid_concept(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Mock Stripe responses
        $mockSession = $this->createStripeSession();

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                'cus_test123456789012345678901',
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '500.00',
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que se creó el pago en la base de datos
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '500.00',
            'stripe_session_id' => 'cs_test_123456789012345678901234',
            'status' => PaymentStatus::UNPAID->value,
        ]);
    }

    #[Test]
    public function it_creates_stripe_customer_when_user_does_not_have_one(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => null,
            'email' => 'test_' . uniqid() . '@example.com',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Mock Stripe responses
        $mockSession = $this->createStripeSession();

        $this->stripeMock->shouldReceive('createStripeUser')
            ->once()
            ->with($userEntity)
            ->andReturn('cus_new_test_customer_1234567890');

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                'cus_new_test_customer_1234567890',
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '500.00',
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que se actualizó el customer_id
        $user->refresh();
        $this->assertEquals('cus_new_test_customer_1234567890', $user->stripe_customer_id);
    }

    #[Test]
    public function it_throws_exception_when_concept_not_found(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $nonExistentConceptId = 99999;

        // Expect exception
        $this->expectException(ConceptNotFoundException::class);

        // Act
        $this->useCase->execute($userEntity, $nonExistentConceptId);
    }

    #[Test]
    public function it_throws_exception_when_concept_is_inactive(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::DESACTIVADO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Expect exception
        $this->expectException(ConceptInactiveException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_throws_exception_when_concept_has_future_start_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(30),
        ]);

        // Expect exception
        $this->expectException(ConceptNotStartedException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_throws_exception_when_concept_has_past_end_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Expect exception
        $this->expectException(ConceptExpiredException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_updates_existing_underpaid_payment(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Crear pago existente subpagado
        $existingPayment = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1000.00',
            'amount_received' => '400.00',
            'status' => PaymentStatus::UNDERPAID->value,
            'stripe_session_id' => 'cs_test_old123456789012345678901234567890123456',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        // Mock Stripe
        $mockSession = $this->createStripeSession(['id' => 'cs_test_new123456789012345678901234567890123456']);

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                'cus_test123456789012345678901',
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '600.00', // Monto pendiente: 1000 - 400 = 600
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que NO se creó un nuevo pago
        $paymentCount = PaymentModel::where('user_id', $user->id)
            ->where('payment_concept_id', $concept->id)
            ->count();

        $this->assertEquals(1, $paymentCount);

        // Verificar que se actualizó el session_id del pago existente
        $existingPayment->refresh();
        $this->assertEquals('cs_test_new123456789012345678901234567890123456', $existingPayment->stripe_session_id);
    }

    #[Test]
    public function it_handles_non_paid_payment_with_recent_creation(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Crear pago existente reciente (menos de 1 hora) SIN amount_received
        $existingPayment = PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '500.00',
            'amount_received' => null, // Importante: null
            'status' => PaymentStatus::DEFAULT->value,
            'stripe_session_id' => 'cs_test_old123456789012345678901234567890123456',
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        // Mock Stripe
        $mockSession = $this->createStripeSession(['id' => 'cs_test_new123456789012345678901234567890123456']);

        $this->stripeMock->shouldReceive('expireSessionIfPending')
            ->once()
            ->with('cs_test_old123456789012345678901234567890123456')
            ->andReturn(true);

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                'cus_test123456789012345678901',
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '500.00',
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que se actualizó el session_id
        $existingPayment->refresh();
        $this->assertEquals('cs_test_new123456789012345678901234567890123456', $existingPayment->stripe_session_id);
    }

    #[Test]
    public function it_throws_exception_when_retrying_non_paid_payment_with_active_session(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Crear pago existente SIN amount_received
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '500.00',
            'amount_received' => null, // Importante: null para que pase la validación
            'status' => PaymentStatus::DEFAULT->value,
            'stripe_session_id' => 'cs_test_active123456789012345678901234567890',
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        // Mock Stripe - NO puede expirar la sesión
        $this->stripeMock->shouldReceive('expireSessionIfPending')
            ->once()
            ->with('cs_test_active123456789012345678901234567890')
            ->andReturn(false);

        // Expect exception
        $this->expectException(PaymentRetryNotAllowedException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_throws_exception_when_retrying_old_non_paid_payment(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Crear pago existente antiguo (más de 1 hora) SIN amount_received
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '500.00',
            'amount_received' => null, // Importante: null
            'status' => PaymentStatus::DEFAULT->value,
            'stripe_session_id' => 'cs_test_old123456789012345678901234567890',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        // Expect exception - no debería llegar a llamar a Stripe
        $this->expectException(PaymentRetryNotAllowedException::class);
        $this->expectExceptionMessage('No se puede volver a pagar: el intento de pago anterior fue hace más de 1 hora.');

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_excludes_concepts_with_user_exceptions(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'name' => 'Test User',
            'last_name' => 'Test Last',
            'email' => 'test_exception@example.com',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Crear concepto con excepción para este usuario
        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->exceptions()->sync($user->id);
        $this->stripeMock->shouldNotReceive('createCheckoutSession');
        $this->stripeMock->shouldNotReceive('createStripeUser');
        // Expect exception
        $this->expectException(UserExplicitlyExcludedException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_filters_concepts_by_career(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);

        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para carrera específica
        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->careers()->attach($career->id);

        // Mock Stripe
        $mockSession = $this->createStripeSession();
        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);
    }

    #[Test]
    public function it_rejects_concept_for_different_career(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);
        $otherCareer = CareerModel::factory()->create(['career_name' => 'Medicina']);

        $user = UserModel::factory()->asStudent()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para otra carrera
        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->careers()->attach($otherCareer->id);

        // Expect exception
        $this->expectException(UserNotAllowedException::class);

        // Act
        $this->useCase->execute($userEntity, $concept->id);
    }

    #[Test]
    public function it_filters_concepts_by_semester(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);

        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 3,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para semestre 3
        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->paymentConceptSemesters()->create(['semestre' => 3]);

        // Mock Stripe
        $mockSession = $this->createStripeSession();
        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);
    }

    #[Test]
    public function it_handles_concept_for_applicant_users(): void
    {
        // Arrange
        $user = UserModel::factory()->create([
            'name' => 'Test Applicant',
            'last_name' => 'Test Last',
            'email' => 'applicant_' . uniqid() . '@example.com',
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $applicantRole = Role::where('name', UserRoles::APPLICANT->value)->firstOrFail();
        $user->assignRole($applicantRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto para aplicantes
        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TAG->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->applicantTypes()->create([
            'tag' => \App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType::APPLICANT->value
        ]);

        // Mock Stripe
        $mockSession = $this->createStripeSession();
        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);
    }

    #[Test]
    public function it_uses_existing_stripe_customer(): void
    {
        // Arrange
        $existingCustomerId = 'cus_existing_test_1234567890123456';
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => $existingCustomerId,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Mock Stripe - NO debe crear nuevo customer
        $this->stripeMock->shouldNotReceive('createStripeUser');

        $mockSession = $this->createStripeSession();
        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                $existingCustomerId,
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '500.00',
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que NO se cambió el customer_id
        $user->refresh();
        $this->assertEquals($existingCustomerId, $user->stripe_customer_id);
    }

    #[Test]
    public function it_handles_empty_stripe_customer_id_string(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => '', // String vacío
            'email' => 'empty_' . uniqid() . '@example.com',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Mock Stripe
        $newCustomerId = 'cus_new_test_1234567890123456';
        $mockSession = $this->createStripeSession();

        $this->stripeMock->shouldReceive('createStripeUser')
            ->once()
            ->with($userEntity)
            ->andReturn($newCustomerId);

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                $newCustomerId,
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '500.00',
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);

        // Verificar que se creó nuevo customer_id
        $user->refresh();
        $this->assertEquals($newCustomerId, $user->stripe_customer_id);
    }


    #[Test]
    public function it_calculates_pending_amount_for_underpaid_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'stripe_customer_id' => 'cus_test123456789012345678901',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '1500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Crear pago subpagado
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1500.00',
            'amount_received' => '400.00',
            'status' => PaymentStatus::UNDERPAID->value,
            'stripe_session_id' => 'cs_test_123456789012345678901234',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        // Mock Stripe - calcular monto pendiente (1500 - 400 = 1100)
        $mockSession = $this->createStripeSession(['id' => 'cs_test_123456789012345678901234']);

        $this->stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->with(
                'cus_test123456789012345678901',
                Mockery::type(\App\Core\Domain\Entities\PaymentConcept::class),
                '1100.00', // Monto pendiente
                $user->id
            )
            ->andReturn($mockSession);

        // Act
        $result = $this->useCase->execute($userEntity, $concept->id);

        // Assert
        $this->assertIsString($result);
        $this->assertEquals('https://checkout.stripe.com/c/pay/test_session', $result);
    }
}
