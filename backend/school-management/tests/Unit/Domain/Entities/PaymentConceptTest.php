<?php

namespace Tests\Unit\Domain\Entities;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\PaymentConcept;
use PHPUnit\Framework\Attributes\Test;

class PaymentConceptTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Colegiatura',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1500.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $startDate = Carbon::create(2024, 1, 15);
        $endDate = Carbon::create(2024, 12, 31);

        $paymentConcept = new PaymentConcept(
            concept_name: 'Inscripción Semestral',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $startDate,
            amount: '2500.50',
            applies_to: PaymentConceptAppliesTo::CARRERA,
            userIds: [1, 2, 3],
            careerIds: [5, 6],
            semesters: [1, 2, 3],
            exceptionUserIds: [10, 11],
            applicantTags: [PaymentConceptApplicantType::NO_STUDENT_DETAILS->value],
            id: 100,
            description: 'Pago de inscripción para el semestre',
            end_date: $endDate
        );

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
        $this->assertEquals('Inscripción Semestral', $paymentConcept->concept_name);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $paymentConcept->status);
        $this->assertEquals($startDate, $paymentConcept->start_date);
        $this->assertEquals('2500.50', $paymentConcept->amount);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA, $paymentConcept->applies_to);
        $this->assertEquals(100, $paymentConcept->id);
        $this->assertEquals('Pago de inscripción para el semestre', $paymentConcept->description);
        $this->assertEquals($endDate, $paymentConcept->end_date);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $startDate = Carbon::now();

        $paymentConcept = new PaymentConcept(
            concept_name: 'Mensualidad',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $startDate,
            amount: '500.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertEquals('Mensualidad', $paymentConcept->concept_name);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $paymentConcept->status);
        $this->assertEquals($startDate, $paymentConcept->start_date);
        $this->assertEquals('500.00', $paymentConcept->amount);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $paymentConcept->applies_to);
        $this->assertNull($paymentConcept->id);
        $this->assertNull($paymentConcept->description);
        $this->assertNull($paymentConcept->end_date);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $startDate = Carbon::create(2024, 6, 1);

        $paymentConcept = new PaymentConcept(
            concept_name: 'Examen de Admisión',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $startDate,
            amount: '800.00',
            applies_to: PaymentConceptAppliesTo::TAG,
            applicantTags: [PaymentConceptApplicantType::APPLICANT->value],
            id: 50,
            description: 'Pago para presentar examen de admisión'
        );

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
        $this->assertEquals('Examen de Admisión', $paymentConcept->concept_name);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $paymentConcept->status);
        $this->assertEquals($startDate, $paymentConcept->start_date);
        $this->assertEquals('800.00', $paymentConcept->amount);
        $this->assertEquals(PaymentConceptAppliesTo::TAG, $paymentConcept->applies_to);
        $this->assertEquals(50, $paymentConcept->id);
        $this->assertEquals('Pago para presentar examen de admisión', $paymentConcept->description);
    }

    #[Test]
    public function it_checks_status_correctly()
    {
        $startDate = Carbon::now();

        $activeConcept = new PaymentConcept(
            concept_name: 'Activo',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $startDate,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $disabledConcept = new PaymentConcept(
            concept_name: 'Desactivado',
            status: PaymentConceptStatus::DESACTIVADO,
            start_date: $startDate,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $finalizedConcept = new PaymentConcept(
            concept_name: 'Finalizado',
            status: PaymentConceptStatus::FINALIZADO,
            start_date: $startDate,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $deletedConcept = new PaymentConcept(
            concept_name: 'Eliminado',
            status: PaymentConceptStatus::ELIMINADO,
            start_date: $startDate,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertTrue($activeConcept->isActive());
        $this->assertFalse($activeConcept->isDisable());
        $this->assertFalse($activeConcept->isFinalize());
        $this->assertFalse($activeConcept->isDelete());

        $this->assertFalse($disabledConcept->isActive());
        $this->assertTrue($disabledConcept->isDisable());
        $this->assertFalse($disabledConcept->isFinalize());
        $this->assertFalse($disabledConcept->isDelete());

        $this->assertFalse($finalizedConcept->isActive());
        $this->assertFalse($finalizedConcept->isDisable());
        $this->assertTrue($finalizedConcept->isFinalize());
        $this->assertFalse($finalizedConcept->isDelete());

        $this->assertFalse($deletedConcept->isActive());
        $this->assertFalse($deletedConcept->isDisable());
        $this->assertFalse($deletedConcept->isFinalize());
        $this->assertTrue($deletedConcept->isDelete());
    }

    #[Test]
    public function it_detects_expired_concepts()
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        $expiredConcept = new PaymentConcept(
            concept_name: 'Expirado',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::create(2024, 1, 1),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: $yesterday
        );

        $this->assertTrue($expiredConcept->isExpired());

        $validConcept = new PaymentConcept(
            concept_name: 'Válido',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::create(2024, 1, 1),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: $tomorrow
        );

        $this->assertFalse($validConcept->isExpired());

        $noEndDateConcept = new PaymentConcept(
            concept_name: 'Sin fecha fin',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::create(2024, 1, 1),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertFalse($noEndDateConcept->isExpired());
    }

    #[Test]
    public function it_detects_if_concept_has_started()
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        $startedConcept = new PaymentConcept(
            concept_name: 'Comenzó',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $yesterday,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertTrue($startedConcept->hasStarted());

        $startsTodayConcept = new PaymentConcept(
            concept_name: 'Comienza hoy',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $today,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertTrue($startsTodayConcept->hasStarted());

        $startsTomorrowConcept = new PaymentConcept(
            concept_name: 'Comienza mañana',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $tomorrow,
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertFalse($startsTomorrowConcept->hasStarted());
    }

    #[Test]
    public function it_checks_user_membership()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES,
            userIds: [1, 5, 10, 15]
        );

        $this->assertTrue($paymentConcept->hasUser(1));
        $this->assertTrue($paymentConcept->hasUser(5));
        $this->assertTrue($paymentConcept->hasUser(10));
        $this->assertTrue($paymentConcept->hasUser(15));
        $this->assertFalse($paymentConcept->hasUser(2));
        $this->assertFalse($paymentConcept->hasUser(99));
        $this->assertFalse($paymentConcept->hasUser(0));
    }

    #[Test]
    public function it_checks_career_membership()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::CARRERA,
            careerIds: [101, 202, 303]
        );

        $this->assertTrue($paymentConcept->hasCareer(101));
        $this->assertTrue($paymentConcept->hasCareer(202));
        $this->assertTrue($paymentConcept->hasCareer(303));
        $this->assertFalse($paymentConcept->hasCareer(102));
        $this->assertFalse($paymentConcept->hasCareer(404));
        $this->assertFalse($paymentConcept->hasCareer(0));
    }

    #[Test]
    public function it_checks_semester_membership()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::SEMESTRE,
            semesters: [1, 2, 3, '4', '5']
        );

        $this->assertTrue($paymentConcept->hasSemester(1));
        $this->assertTrue($paymentConcept->hasSemester(2));
        $this->assertTrue($paymentConcept->hasSemester(3));
        $this->assertTrue($paymentConcept->hasSemester('4'));
        $this->assertTrue($paymentConcept->hasSemester('5'));
        $this->assertFalse($paymentConcept->hasSemester(6));
        $this->assertFalse($paymentConcept->hasSemester('7'));
        $this->assertFalse($paymentConcept->hasSemester(0));

        $this->assertTrue($paymentConcept->hasSemester('1'));
        $this->assertTrue($paymentConcept->hasSemester(2));
    }

    #[Test]
    public function it_checks_user_exceptions()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            exceptionUserIds: [50, 60, 70]
        );

        $this->assertTrue($paymentConcept->hasExceptionForUser(50));
        $this->assertTrue($paymentConcept->hasExceptionForUser(60));
        $this->assertTrue($paymentConcept->hasExceptionForUser(70));
        $this->assertFalse($paymentConcept->hasExceptionForUser(1));
        $this->assertFalse($paymentConcept->hasExceptionForUser(51));
        $this->assertFalse($paymentConcept->hasExceptionForUser(0));
    }

    #[Test]
    public function it_checks_tag_membership()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TAG,
            applicantTags: [
                PaymentConceptApplicantType::NO_STUDENT_DETAILS,
                PaymentConceptApplicantType::APPLICANT,
            ]
        );

        $this->assertTrue($paymentConcept->hasTag(PaymentConceptApplicantType::NO_STUDENT_DETAILS));
        $this->assertTrue($paymentConcept->hasTag(PaymentConceptApplicantType::APPLICANT));

        $this->assertTrue($paymentConcept->hasTag(PaymentConceptApplicantType::NO_STUDENT_DETAILS->value));
        $this->assertTrue($paymentConcept->hasTag(PaymentConceptApplicantType::APPLICANT->value));
        $this->assertFalse($paymentConcept->hasTag('custom_tag'));
        $this->assertFalse($paymentConcept->hasTag('non_existent_tag'));
        $this->assertFalse($paymentConcept->hasTag(''));
    }

    #[Test]
    public function it_can_be_serialized_to_array()
    {
        $startDate = Carbon::create(2024, 6, 15);
        $endDate = Carbon::create(2024, 12, 31);

        $paymentConcept = new PaymentConcept(
            concept_name: 'Laboratorio de Computación',
            status: PaymentConceptStatus::ACTIVO,
            start_date: $startDate,
            amount: '750.25',
            applies_to: PaymentConceptAppliesTo::CARRERA,
            userIds: [100, 101],
            careerIds: [1, 2],
            semesters: [3, 4, 5],
            exceptionUserIds: [200],
            applicantTags: [PaymentConceptApplicantType::APPLICANT->value],
            id: 25,
            description: 'Pago por uso de laboratorio',
            end_date: $endDate
        );

        $array = $paymentConcept->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('concept_name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('applies_to', $array);
        $this->assertArrayHasKey('user_ids', $array);
        $this->assertArrayHasKey('career_ids', $array);
        $this->assertArrayHasKey('semesters', $array);
        $this->assertArrayHasKey('exception_user_ids', $array);
        $this->assertArrayHasKey('applicant_tags', $array);

        $this->assertEquals(25, $array['id']);
        $this->assertEquals('Laboratorio de Computación', $array['concept_name']);
        $this->assertEquals('Pago por uso de laboratorio', $array['description']);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $array['status']);
        $this->assertEquals($startDate, $array['start_date']);
        $this->assertEquals($endDate, $array['end_date']);
        $this->assertEquals('750.25', $array['amount']);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA, $array['applies_to']);
        $this->assertEquals([100, 101], $array['user_ids']);
        $this->assertEquals([1, 2], $array['career_ids']);
        $this->assertEquals([3, 4, 5], $array['semesters']);
        $this->assertEquals([200], $array['exception_user_ids']);
        $this->assertEquals([PaymentConceptApplicantType::APPLICANT->value], $array['applicant_tags']);
    }

    #[Test]
    public function it_can_be_created_from_array()
    {
        $data = [
            'id' => 30,
            'concept_name' => 'Biblioteca',
            'description' => 'Cuota de biblioteca',
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => '2024-06-01 00:00:00',
            'end_date' => '2024-12-31 23:59:59',
            'amount' => '300.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'user_ids' => [1, 2, 3],
            'career_ids' => [],
            'semesters' => [],
            'exception_user_ids' => [99],
            'applicant_tags' => [PaymentConceptApplicantType::APPLICANT->value]
        ];

        $paymentConcept = PaymentConcept::fromArray($data);

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
        $this->assertEquals(30, $paymentConcept->id);
        $this->assertEquals('Biblioteca', $paymentConcept->concept_name);
        $this->assertEquals('Cuota de biblioteca', $paymentConcept->description);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $paymentConcept->status);
        $this->assertEquals('2024-06-01 00:00:00', $paymentConcept->start_date->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-12-31 23:59:59', $paymentConcept->end_date->format('Y-m-d H:i:s'));
        $this->assertEquals('300.00', $paymentConcept->amount);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $paymentConcept->applies_to);
        $this->assertEquals([1, 2, 3], $paymentConcept->getUserIds());
        $this->assertEquals([], $paymentConcept->getCareerIds());
        $this->assertEquals([], $paymentConcept->getSemesters());
        $this->assertEquals([99], $paymentConcept->getExceptionUsersIds());
        $this->assertEquals([PaymentConceptApplicantType::APPLICANT->value], $paymentConcept->getApplicantTag());
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Deportes',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '200.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            id: 40,
            description: 'Cuota deportiva'
        );

        $json = json_encode($paymentConcept);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(40, $decoded['id']);
        $this->assertEquals('Deportes', $decoded['concept_name']);
        $this->assertEquals('Cuota deportiva', $decoded['description']);
        $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $decoded['status']);
        $this->assertEquals('200.00', $decoded['amount']);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $decoded['applies_to']);
    }

    #[Test]
    public function it_handles_setters_and_getters()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $paymentConcept->setUserIds([10, 20, 30]);
        $this->assertEquals([10, 20, 30], $paymentConcept->getUserIds());

        $paymentConcept->setCareerIds([5, 6]);
        $this->assertEquals([5, 6], $paymentConcept->getCareerIds());

        $paymentConcept->setSemesters([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $paymentConcept->getSemesters());

        $paymentConcept->setExceptionUsersIds([99, 100]);
        $this->assertEquals([99, 100], $paymentConcept->getExceptionUsersIds());

        $paymentConcept->setApplicantTag([PaymentConceptApplicantType::NO_STUDENT_DETAILS->value]);
        $this->assertEquals([PaymentConceptApplicantType::NO_STUDENT_DETAILS->value], $paymentConcept->getApplicantTag());

        $array = $paymentConcept->toArray();
        $this->assertEquals([10, 20, 30], $array['user_ids']);
        $this->assertEquals([5, 6], $array['career_ids']);
        $this->assertEquals([1, 2, 3], $array['semesters']);
        $this->assertEquals([99, 100], $array['exception_user_ids']);
        $this->assertEquals([PaymentConceptApplicantType::NO_STUDENT_DETAILS->value], $array['applicant_tags']);
    }

    #[Test]
    public function it_handles_empty_arrays_for_collections()
    {
        $paymentConcept = new PaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '100.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
        );

        $this->assertEquals([], $paymentConcept->getUserIds());
        $this->assertEquals([], $paymentConcept->getCareerIds());
        $this->assertEquals([], $paymentConcept->getSemesters());
        $this->assertEquals([], $paymentConcept->getExceptionUsersIds());
        $this->assertEquals([], $paymentConcept->getApplicantTag());

        $array = $paymentConcept->toArray();
        $this->assertEquals([], $array['user_ids']);
        $this->assertEquals([], $array['career_ids']);
        $this->assertEquals([], $array['semesters']);
        $this->assertEquals([], $array['exception_user_ids']);
        $this->assertEquals([], $array['applicant_tags']);
    }

    #[Test]
    public function it_accepts_different_applies_to_values()
    {
        $appliesToValues = [
            PaymentConceptAppliesTo::TODOS,
            PaymentConceptAppliesTo::ESTUDIANTES,
            PaymentConceptAppliesTo::CARRERA,
            PaymentConceptAppliesTo::SEMESTRE,
            PaymentConceptAppliesTo::TAG,
        ];

        foreach ($appliesToValues as $appliesTo) {
            $paymentConcept = new PaymentConcept(
                concept_name: 'Test ' . $appliesTo->value,
                status: PaymentConceptStatus::ACTIVO,
                start_date: Carbon::now(),
                amount: '100.00',
                applies_to: $appliesTo,
            );

            $this->assertEquals($appliesTo, $paymentConcept->applies_to);
        }
    }

    #[Test]
    public function it_accepts_different_status_values()
    {
        $statusValues = [
            PaymentConceptStatus::ACTIVO,
            PaymentConceptStatus::DESACTIVADO,
            PaymentConceptStatus::FINALIZADO,
            PaymentConceptStatus::ELIMINADO,
        ];

        foreach ($statusValues as $status) {
            $paymentConcept = new PaymentConcept(
                concept_name: 'Test ' . $status->value,
                status: $status,
                start_date: Carbon::now(),
                amount: '100.00',
                applies_to: PaymentConceptAppliesTo::TODOS,
            );

            $this->assertEquals($status, $paymentConcept->status);
        }
    }

    #[Test]
    public function it_handles_from_array_with_null_values()
    {
        $data = [
            'concept_name' => 'Test',
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => '2024-01-01',
            'amount' => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
        ];

        $paymentConcept = PaymentConcept::fromArray($data);

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
        $this->assertNull($paymentConcept->id);
        $this->assertNull($paymentConcept->description);
        $this->assertNull($paymentConcept->end_date);
        $this->assertEquals([], $paymentConcept->getUserIds());
        $this->assertEquals([], $paymentConcept->getCareerIds());
        $this->assertEquals([], $paymentConcept->getSemesters());
        $this->assertEquals([], $paymentConcept->getExceptionUsersIds());
        $this->assertEquals([], $paymentConcept->getApplicantTag());
    }

    #[Test]
    public function it_handles_from_array_with_objects_instead_of_strings()
    {
        $data = [
            'concept_name' => 'Test',
            'status' => PaymentConceptStatus::ACTIVO,
            'start_date' => Carbon::now(),
            'amount' => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS,
            'user_ids' => [1, 2],
            'career_ids' => [3, 4],
            'semesters' => [1],
            'exception_user_ids' => [5],
            'applicant_tags' => [PaymentConceptApplicantType::NO_STUDENT_DETAILS],
            'id' => 10,
            'description' => 'Descripción',
            'end_date' => Carbon::now()->addMonth()
        ];

        $paymentConcept = PaymentConcept::fromArray($data);

        $this->assertInstanceOf(PaymentConcept::class, $paymentConcept);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $paymentConcept->status);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $paymentConcept->applies_to);
        $this->assertInstanceOf(Carbon::class, $paymentConcept->start_date);
        $this->assertInstanceOf(Carbon::class, $paymentConcept->end_date);
    }
}
