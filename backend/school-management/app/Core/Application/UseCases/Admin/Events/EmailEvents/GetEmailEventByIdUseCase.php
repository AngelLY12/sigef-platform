<?php

namespace App\Core\Application\UseCases\Admin\Events\EmailEvents;

use App\Core\Application\DTO\Response\Events\EmailEvent\EmailEventByIdResponse;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;
use App\Exceptions\NotFound\NotFoundException;

class GetEmailEventByIdUseCase
{
    public function __construct(
        private EmailEventQueryRepInterface $emailEventQueryRep,
    ){}

    public function execute(int $emailEventId): EmailEventByIdResponse
    {
        $event = $this->emailEventQueryRep->findById($emailEventId);
        if(!$event){
            throw new NotFoundException('No se encontro el evento de correo solicitado');
        }
        return EmailEventByIdResponse::create($event);
    }

}
