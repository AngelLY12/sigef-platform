<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Facades\Payments\Student\PaymentHistoryService;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Http\Controllers\Concerns\ResolvesRequestUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\General\ForceRefreshRequest;
use App\Http\Requests\General\PaginationRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Payment history",
 *     description="Endpoints relacionados con el historial de pagos del usuario"
 * )
 */
class PaymentHistoryController extends Controller
{
    use ResolvesRequestUser;
    protected PaymentHistoryService $paymentHistoryService;
    public function __construct(PaymentHistoryService $paymentHistoryService){
        $this->paymentHistoryService= $paymentHistoryService;

    }


    public function index(PaginationRequest $request)
    {
        $user = $this->targetUser($request);
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;

        $perPage = $request->integer('perPage', 15);
        $page = $request->integer('page', 1);
        $history=$this->paymentHistoryService->paymentHistory(UserMapper::toDomain($user), $perPage, $page, $forceRefresh);
        return Response::success(
            ['payment_history' => $history],
            empty($history->items) ? 'No hay historial de pagos para este usuario.' : null
        );

    }

    public function findPayment(ForceRefreshRequest $request, int $id)
    {
        $forceRefresh = $request->validated()['forceRefresh'] ?? false;
        $payment=$this->paymentHistoryService->findPayment($id, $forceRefresh);
        return Response::success(['payment' => $payment], 'Pago encontrado.');

    }

    public function receiptPDF(int $paymentId)
    {
        $path = $this->paymentHistoryService->receiptFromPayment($paymentId);
        $url = Storage::temporaryUrl(
            $path,
            now()->addMinutes(5),
            [
                'response-content-disposition' => 'inline',
            ]
        );
        return Response::success([
            'url' => $url,
            'expires_in' => 300,
            'content_type' => 'text/html'
        ], 'URL del comprobante');
    }
}
