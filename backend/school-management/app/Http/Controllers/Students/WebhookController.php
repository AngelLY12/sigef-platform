<?php

namespace App\Http\Controllers\Students;

use App\Core\Application\Services\Facades\Payments\Stripe\WebhookServiceFacades;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Webhook;

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
                    /** @var Session $obj */
                    $result =$this->webhookService->sessionCompleted($obj, $eventId);
                    return Response::success(
                        null,
                        $result ? 'Se completó la sesión' : 'No se pudo procesar la sesión'
                    );
                    break;

                case 'charge.succeeded':
                    /** @var Charge $obj */
                    $result = $this->webhookService->chargeSucceeded($obj, $eventId);
                    return Response::success(
                        $result,
                        'El pago fue validado correctamente'
                    );
                    break;

                case 'payment_intent.succeeded':
                    /** @var PaymentIntent $obj */
                    $result = $this->webhookService->paymentIntentSucceeded($obj, $eventId);
                    return Response::success(
                        $result,
                        'Evento registrado correctamente'
                    );
                    break;
                case 'payment_intent.payment_failed':
                case 'payment_intent.canceled':
                case 'checkout.session.expired':
                    /** @var Session|PaymentIntent $obj */
                    $result = $this->webhookService->handleFailedOrExpiredPayment($obj,$eventType, $eventId);
                    if(!$result)
                    {
                        return Response::success(null, "Fallo el evento :" . $messageMap[$eventType] ?? 'Evento procesado');
                    }
                    return Response::success(null, $messageMap[$eventType] ?? 'Evento procesado');
                    break;
                case 'payment_method.attached':
                    /** @var PaymentMethod $obj */
                    $result = $this->webhookService->paymentMethodAttached($obj, $eventId);
                    if (!$result) {
                        return Response::success(null, 'El método de pago ya existe');
                    }
                    return Response::success(null, 'Se creó el método de pago');
                    break;
                case 'payment_method.detached':
                    /** @var PaymentMethod $obj */
                    $result = $this->webhookService->detachPaymentMethod($obj, $eventType ,$eventId);
                    if(!$result)
                    {
                        return Response::success(null, 'Hubo un error al eliminar el metodo de pago');
                    }
                    return Response::success(null, 'Se creó elimino método de pago');
                    break;
                case 'payment_method.automatically_updated':
                    /** @var PaymentMethod $obj */
                    $result = $this->webhookService->updatePaymentMethodAutomatically($obj, $eventType ,$eventId);
                    if(!$result)
                    {
                        return Response::success(null, 'Hubo un error al actualizar el metodo de pago');
                    }
                    return Response::success(null, 'Se actualizo el método de pago');
                    break;
                case 'checkout.session.async_payment_succeeded':
                    /** @var Session $obj */
                    $result = $this->webhookService->sessionAsync($obj, $eventId);
                    return Response::success(
                        null,
                        $result
                            ? 'Se actualizó el estado del pago'
                            : 'No se pudo actualizar el estado del pago'
                    );
                    break;
                case 'payment_intent.requires_action':
                    /** @var PaymentIntent $obj */
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
