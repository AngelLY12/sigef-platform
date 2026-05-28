<?php

namespace App\Core\Infraestructure\Mappers;

use App\Models\PaymentConcept;
use App\Core\Domain\Entities\PaymentConcept as DomainPaymentConcept;


class PaymentConceptMapper{

    public static function toDomain(PaymentConcept $paymentConcept){
        $domain= new DomainPaymentConcept(
            concept_name: $paymentConcept->concept_name,
            status: $paymentConcept->status,
            start_date: $paymentConcept->start_date,
            amount: $paymentConcept->amount,
            applies_to: $paymentConcept->applies_to,
            id: $paymentConcept->id,
            description: $paymentConcept->description,
            end_date: $paymentConcept->end_date
        );
        if($paymentConcept->relationLoaded('careers'))
        {
            $domain->setCareerIds($paymentConcept->careers->pluck('id')->toArray());
        }
        if($paymentConcept->relationLoaded('users')){
            $domain->setUserIds($paymentConcept->users->pluck('id')->toArray());

        }
        if($paymentConcept->relationLoaded('paymentConceptSemesters'))
        {
            $domain->setSemesters($paymentConcept->paymentConceptSemesters->pluck('semestre')->toArray());
        }
        if($paymentConcept->relationLoaded('exceptions'))
        {
            $domain->setExceptionUsersIds($paymentConcept->exceptions->pluck('pivot.user_id')->toArray());
        }
        if($paymentConcept->relationLoaded('applicantTypes'))
        {
            $domain->setApplicantTag($paymentConcept->applicantTypes->pluck('tag')->toArray());
        }

        return $domain;
    }

    public static function toPersistence(DomainPaymentConcept $concept): array
    {
        return [
            'concept_name' => $concept->concept_name,
            'description'  => $concept->description,
            'status'       => $concept->status,
            'start_date'   => $concept->start_date,
            'end_date'     => $concept->end_date,
            'amount'       => $concept->amount,
            'applies_to'   => $concept->applies_to,
        ];
    }

}
