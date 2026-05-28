<?php
namespace App\Core\Domain\Repositories\Command\Payments;

use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Domain\Entities\PaymentConcept;


interface PaymentConceptRepInterface {
     //CRUD Methods
    public function create(PaymentConcept $concept): PaymentConcept;
    public function update(int $conceptId, array $data): PaymentConcept;
    public function deleteLogical(PaymentConcept $concept): PaymentConcept;
    public function delete(int $conceptId): void;
     //Attach and detach Methods
    public function attachToUsers(int $conceptId, UserIdListDTO $userIds, bool $replaceRelations=false): PaymentConcept;
    public function attachToCareer(int $conceptId, array $careerIds, bool $replaceRelations=false): PaymentConcept;
    public function attachToSemester(int $conceptId, array $semesters, bool $replaceRelations=false): PaymentConcept;
    public function attachToExceptionStudents(int $conceptId, UserIdListDTO $userIds, bool $replaceRelations=false): PaymentConcept;
    public function attachToApplicantTag(int $conceptId, array $tags, bool $replaceRelations = false): PaymentConcept;
    public function detachFromCareer(int $conceptId): void;
    public function detachFromSemester(int $conceptId): void;
    public function detachFromUsers(int $conceptId): void;
    public function detachFromExceptionStudents(int $conceptId):void;
    public function detachFromApplicantTag(int $conceptId):void;
    //Other
    public function finalize(PaymentConcept $concept): PaymentConcept;
    public function disable(PaymentConcept $concept): PaymentConcept;
    public function activate(PaymentConcept $concept): PaymentConcept;
    public function cleanDeletedConcepts():int;
    public function finalizePaymentConcepts(): array;

}
