<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Payments\Stripe\WebhookServiceFacades;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use App\Http\Controllers\Controller;
use App\Jobs\ReconcilePayments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;
use Stripe\Stripe;

class WebhookController extends Controller

{
    protected WebhookServiceFacades $webhookService;

    public function __construct(WebhookServiceFacades $webhookService){
        $this->webhookService=$webhookService;
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            $obj = $event->data->object;
            $eventType=$event->type;
            $eventId = $event->id;

            $messageMap = [
                'payment_intent.payment_failed' => 'El pago falló',
                'payment_intent.canceled' => 'El pago fue cancelado',
                'checkout.session.expired' => 'La sesión de pago expiró'
            ];
            switch($eventType){

                case 'checkout.session.completed':
                    $result =$this->webhookService->sessionCompleted($obj, $eventId);
                    if($obj->payment_status==='paid' && $result){
                        $reconciliation =$this->webhookService->reconcilePayment($eventId, $obj->id);
                        return Response::success($reconciliation, "Se reconcilio el pago del evento {$eventId}");
                    }
                    return Response::success(null, 'Se completó la sesión');
                    break;
                case 'payment_intent.payment_failed':
                case 'payment_intent.canceled':
                case 'checkout.session.expired':
                    $result = $this->webhookService->handleFailedOrExpiredPayment($obj,$eventType, $eventId);
                    if(!$result)
                    {
                        return Response::success(null, "Fallo el evento :" . $messageMap[$eventType] ?? 'Evento procesado');
                    }
                    return Response::success(null, $messageMap[$eventType] ?? 'Evento procesado');
                    break;
                case 'payment_method.attached':
                    $result = $this->webhookService->paymentMethodAttached($obj, $eventId);
                    if (!$result) {
                        return Response::success(null, 'El método de pago ya existe');
                    }
                    return Response::success(null, 'Se creó el método de pago');
                    break;
                case 'payment_method.detached':
                    $result = $this->webhookService->detachPaymentMethod($obj, $eventType ,$eventId);
                    if(!$result)
                    {
                        return Response::success(null, 'Hubo un error al eliminar el metodo de pago');
                    }
                    return Response::success(null, 'Se creó elimino método de pago');
                    break;
                case 'payment_method.automatically_updated':
                    $result = $this->webhookService->updatePaymentMethodAutomatically($obj, $eventType ,$eventId);
                    if(!$result)
                    {
                        return Response::success(null, 'Hubo un error al actualizar el metodo de pago');
                    }
                    return Response::success(null, 'Se actualizo el método de pago');
                    break;
                case 'checkout.session.async_payment_succeeded':
                    $result = $this->webhookService->sessionAsync($obj, $eventId);
                    if($result)
                    {
                        $reconciliation=$this->webhookService->reconcilePayment($eventId, $obj->id);
                        return Response::success($reconciliation, "Se reconcilio el pago del evento {$eventId}");
                    }
                    return Response::success(null, 'Se actualizó el estado del pago');
                    break;
                case 'payment_intent.requires_action':
                    $this->webhookService->requiresAction($obj, $eventId);
                    return Response::success(null, 'Se notificó correctamente al usuario');
                    break;
                default:
                    return Response::success(null, 'Evento no manejado');
            }

        }  catch (ModelNotFoundException $e) {
            logger()->warning("Recurso no encontrado en webhook: " . $e->getMessage());
            return Response::error('Recurso no encontrado', 404);

        }
        catch (SignatureVerificationException $e) {
            return Response::error('Firma inválida', 400);

        }catch (\Exception $e) {
            logger()->error('Stripe Webhook Error: ' . $e->getMessage());
            return Response::error('Error interno', 500);
        }

    }
}
