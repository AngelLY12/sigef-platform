<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Request\General\LoginDTO;
use App\Core\Application\DTO\Response\General\LoginResponse;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Application\DTO\Response\General\PermissionToDisplay;
use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Core\Application\DTO\Response\General\StripePaymentsResponse;
use App\Core\Application\DTO\Response\General\StripePayoutResponse;
use App\Core\Domain\Utils\Helpers\Money;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;

class GeneralMapper{
    public static function toPaginatedResponse(?array $items, LengthAwarePaginator $paginated){
        return new PaginatedResponse(
            items: $items ?? [],
            currentPage: $paginated->currentPage() ?? null,
            lastPage: $paginated->lastPage() ?? null,
            perPage: $paginated->perPage() ?? null,
            total: $paginated->total() ?? null,
            hasMorePages: $paginated->hasMorePages(),
            nextPage: $paginated->currentPage() < $paginated->lastPage() ? $paginated->currentPage() + 1 : null,
            previousPage: $paginated->currentPage() > 1
                ? $paginated->currentPage() - 1
                : null
        );
    }

    public static function toLoginDTO(array $data):LoginDTO
    {
        return new LoginDTO(
            email:$data['email'],
            password:$data['password']
        );
    }

    public static function toLoginResponse(?string $token, ?string $refresh,$token_type, ?array $data):LoginResponse
    {
        return new LoginResponse(
            access_token:$token ?? null,
            refresh_token: $refresh ?? null,
            token_type:$token_type ?? null,
            user_data:$data ?? []
        );
    }

    public static function toStripePaymentResponse(Session $session): StripePaymentsResponse
    {
            $metadata = $session->metadata ? $session->metadata->toArray() : [];
            return new StripePaymentsResponse(
                id: $session->id ?? null,
                payment_intent_id: $session->payment_intent ?? null,
                concept_name: $metadata['concept_name'] ?? null,
                status: $session->payment_status_detailed ?? $session->payment_status ?? null,
                amount_total: $session->amount_total !== null ? Money::from((string) $session->amount_total)->divide('100')->finalize() : null,
                amount_received: $session->amount_received !==null ? Money::from((string) $session->amount_received)->divide('100')->finalize() : '0.00',
                created:$session->created ? date('Y-m-d H:i:s', $session->created) : null,
                receipt_url: $session->receipt_url ?? null
            );
    }

    public static function toPermissionsByUsers(array $roles, array $users, array $permissions): PermissionsByUsers
    {
        return new PermissionsByUsers(
            roles: $roles,
            users: $users,
            permissions: $permissions
        );
    }

    public static function toPermissionsByRole(string $role, int $usersCount, array $permissions): PermissionsByRole
    {
        return new PermissionsByRole(
            role: $role,
            usersCount: $usersCount,
            permissions: $permissions
        );
    }

    public static function toStripePayoutResponse(array $data):StripePayoutResponse
    {
        return StripePayoutResponse::fromArray($data);
    }

    public static function toRolesUpdatedToUserResponse(User $user, array $roles): RolesUpdatedToUserResponse
    {
        return new RolesUpdatedToUserResponse(
            userId: $user->id,
            fullName: $user->name . ' ' . $user->last_name,
            roles: $roles
        );
    }

    public static function toPermissionsUpdatedToUserResponse(User $user, array $permissions): PermissionsUpdatedToUserResponse
    {
        return new PermissionsUpdatedToUserResponse(
            userId: $user->id,
            fullName: $user->name . ' ' . $user->last_name,
            permissions: $permissions
        );
    }

    public static function toPermissionToDisplay(Permission $permission): PermissionToDisplay
    {
        $allConfig = config('permissions_ui');
        $ui = $allConfig[$permission->name] ?? null;
        return new PermissionToDisplay(
            id: $permission->id,
            name: $permission->name,
            type: $permission->type,
            label: $ui['label'] ?? $permission->name,
            group: $ui['group'] ?? null,
        );
    }
}
