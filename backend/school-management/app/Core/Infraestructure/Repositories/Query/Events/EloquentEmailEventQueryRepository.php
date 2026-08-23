<?php

namespace App\Core\Infraestructure\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventFilters;
use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventHistoryFilters;
use App\Core\Application\DTO\Response\Events\EmailEvent\EmailEventResponse;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Repositories\Query\Events\EmailEventQueryRepInterface;
use App\Core\Infraestructure\Mappers\EmailEventMapper;
use App\Models\EmailEvent as EloquentEmailEvent;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentEmailEventQueryRepository implements EmailEventQueryRepInterface
{
    public function findById(int $id): ?EmailEvent
    {
        $eloquent = EloquentEmailEvent::find($id);
        return $eloquent ? EmailEventMapper::toDomain($eloquent) : null;
    }
   public function findBySource(EmailEventSourceType $sourceType, string $sourceId, EmailEventType $eventType): ?EmailEvent
   {
       $eloquent = EloquentEmailEvent::where('source_id', $sourceId)
           ->where('source_type', $sourceType)
           ->where('event_type', $eventType)
           ->first();
       return $eloquent ? EmailEventMapper::toDomain($eloquent) : null;
   }

   public function getAllEmailEvents(EmailEventFilters $filters): LengthAwarePaginator
   {
       return EloquentEmailEvent::query()
           ->select([
               'email_events.id',
               'email_events.user_id',
               'email_events.event_type',
               'email_events.status',
               'email_events.recipient_email',
               'email_events.created_at',
               'users.name',
               'users.last_name'
           ])
           ->leftJoin(
               'users',
               'users.id',
               '=',
               'email_events.user_id'
           )
           ->when(
               $filters->userId,
               fn ($query, $userId) => $query->where('email_events.user_id', $userId)
           )
           ->when(
               $filters->eventType,
               fn ($query, $eventType) => $query->where('email_events.event_type', $eventType)
           )
           ->when(
               $filters->status,
               fn ($query, $status) => $query->where('email_events.status', $status)
           )
           ->when(
               $filters->recipientEmail,
               fn ($query, $recipientEmail) =>
               $query->where(
                   'email_events.recipient_email',
                   $recipientEmail
               )
           )
           ->when(
               $filters->sourceType,
               fn ($query, $sourceType) =>
               $query->where(
                   'email_events.source_type',
                   $sourceType
               )
           )
           ->when(
               $filters->sourceId,
               fn ($query, $sourceId) =>
               $query->where(
                   'email_events.source_id',
                   $sourceId
               )
           )
           ->when(
               $filters->from,
               fn ($query, $from) =>
               $query->where('email_events.created_at', '>=', $from)
           )
           ->when(
               $filters->to,
               fn ($query, $to) =>
               $query->where('email_events.created_at', '<=', $to)
           )
           ->orderByDesc('email_events.created_at')
           ->orderByDesc('email_events.id')
           ->paginate(
               perPage: $filters->perPage,
               page: $filters->page,
           )
           ->through(
               fn (EloquentEmailEvent $event) => EmailEventResponse::create($event)
           )
           ;
   }

   public function getUserEmailHistory(EmailEventHistoryFilters $filters, int $userId): LengthAwarePaginator
   {
       return EloquentEmailEvent::query()
           ->select([
               'email_events.id',
               'email_events.user_id',
               'email_events.event_type',
               'email_events.status',
               'email_events.recipient_email',
               'email_events.created_at',
               'users.name',
               'users.last_name'
           ])
           ->leftJoin(
               'users',
               'users.id',
               '=',
               'email_events.user_id'
           )
           ->when(
               $filters->eventType,
               fn ($query, $eventType) => $query->where('email_events.event_type', $eventType)
           )
           ->when(
               $filters->status,
               fn ($query, $status) => $query->where('email_events.status', $status)
           )
           ->when(
               $filters->sourceType,
               fn ($query, $sourceType) =>
               $query->where(
                   'email_events.source_type',
                   $sourceType
               )
           )
           ->when(
               $filters->from,
               fn ($query, $from) =>
               $query->where('email_events.created_at', '>=', $from)
           )
           ->when(
               $filters->to,
               fn ($query, $to) =>
               $query->where('email_events.created_at', '<=', $to)
           )
           ->where('email_events.user_id', $userId)
           ->orderByDesc('email_events.created_at')
           ->orderByDesc('email_events.id')
           ->paginate(
               perPage: $filters->perPage,
               page: $filters->page,
           )
           ->through(
               fn (EloquentEmailEvent $event) =>
               EmailEventResponse::create($event)
           );
   }

}
