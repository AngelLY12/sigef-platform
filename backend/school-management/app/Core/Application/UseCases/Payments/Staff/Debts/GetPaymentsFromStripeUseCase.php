<?php

namespace App\Core\Application\UseCases\Payments\Staff\Debts;

use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Validation\ValidationException;

class GetPaymentsFromStripeUseCase
{
     public function __construct(
        public UserQueryRepInterface $uqRepo,
        public StripeGatewayQueryInterface $stripeRepo
    )
    {
    }
    public function execute(string $search, ?int $year):array
    {
        if ($year !== null && ($year < 2024 || $year > (int)date('Y'))) {
            throw new ValidationException("El año especificado no es válido.");
        }
        $student=$this->uqRepo->findBySearch($search);
        if (!$student) {
            throw new UserNotFoundException();
        }
        if (!$student->stripe_customer_id) {
            throw new ValidationException("El estudiante no tiene Stripe ID.");
        }
        $sessions = $this->stripeRepo->getStudentPaymentsFromStripe($student,$year);

        return array_map(fn($s) => GeneralMapper::toStripePaymentResponse($s), $sessions);
    }
}
