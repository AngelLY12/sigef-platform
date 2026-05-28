<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Payments\Student\CardsServiceFacades;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\ForceRefreshRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Cards",
 *     description="Gestión de métodos de pago (tarjetas) asociados a los usuarios"
 * )
 */
class CardsController extends Controller
{
    protected CardsServiceFacades $cardsService;

    public function __construct(CardsServiceFacades $cardsService)
    {
        $this->cardsService=$cardsService;
    }


    public function index(ForceRefreshRequest $request, ?int $studentId=null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $targetUser = $user->resolveTargetUser($studentId);

        if (!$targetUser) {
            return Response::error('Acceso no permitido', 403);
        }
        $cards = $this->cardsService->getUserPaymentMethods($targetUser->id, $forceRefresh);

        return Response::success(
            ['cards' => $cards],
            empty($cards) ? 'No se encontraron métodos de pago.' : null
        );
    }


    public function store()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $session= $this->cardsService->setupCard(UserMapper::toDomain($user));

        return Response::success(
            ['url_checkout' => $session->url],
            null,
            201
        );

    }


    public function destroy(int $paymentMethodId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->cardsService->deletePaymentMethod(UserMapper::toDomain($user),$paymentMethodId);

        return Response::success(
            null,
            'Método de pago eliminado correctamente'
        );
    }
}
