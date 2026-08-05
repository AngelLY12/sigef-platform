<?php

namespace App\Providers;

use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;
use App\Core\Domain\Repositories\Command\Misc\DBRepInterface;
use App\Core\Domain\Repositories\Command\Misc\NotificationRepInterface;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Domain\Repositories\Command\Misc\SemesterPromotionsRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Command\Payments\ReceiptRepInterface;
use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Core\Domain\Repositories\Query\Misc\NotificationQueryRepInterface;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Core\Infraestructure\Repositories\Command\Auth\EloquentAccessTokenRepository;
use App\Core\Infraestructure\Repositories\Command\Auth\EloquentRefreshTokenRepository;
use App\Core\Infraestructure\Repositories\Command\Auth\EloquentRolesAndPermissionsRepository;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentCareerRepository;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentDBRepository;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentNotificationRepository;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentParentInviteRepository;
use App\Core\Infraestructure\Repositories\Command\Misc\EloquentSemesterPromotionsRepository;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentConceptRepository;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentEventRepository;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentMethodRepository;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentRepository;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentReceiptRepository;
use App\Core\Infraestructure\Repositories\Command\User\EloquentParentStudentRepository;
use App\Core\Infraestructure\Repositories\Command\User\EloquentStudentDetailRepository;
use App\Core\Infraestructure\Repositories\Command\User\EloquentUserRepository;
use App\Core\Infraestructure\Repositories\Query\Auth\EloquentRolesAndPermissionQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Misc\EloquentCareerQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Misc\EloquentNotificationQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Misc\EloquentParentInviteQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentConceptQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentEventQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentMethodQueryRepository;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentQueryRepository;
use App\Core\Infraestructure\Repositories\Query\User\EloquentParentStudentQueryRepository;
use App\Core\Infraestructure\Repositories\Query\User\EloquentUserQueryRepository;
use App\Core\Infraestructure\Repositories\Stripe\StripeGateway;
use App\Core\Infraestructure\Repositories\Stripe\StripeGatewayQuery;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StripeGatewayInterface::class, StripeGateway::class);
        $this->app->bind(StripeGatewayQueryInterface::class, StripeGatewayQuery::class);
        $this->app->bind(PaymentMethodRepInterface::class, EloquentPaymentMethodRepository::class);
        $this->app->bind(PaymentMethodQueryRepInterface::class, EloquentPaymentMethodQueryRepository::class);
        $this->app->bind(PaymentRepInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(PaymentQueryRepInterface::class, EloquentPaymentQueryRepository::class);
        $this->app->bind(PaymentConceptRepInterface::class, EloquentPaymentConceptRepository::class);
        $this->app->bind(PaymentConceptQueryRepInterface::class, EloquentPaymentConceptQueryRepository::class);
        $this->app->bind(UserRepInterface::class, EloquentUserRepository::class);
        $this->app->bind(UserQueryRepInterface::class, EloquentUserQueryRepository::class);
        $this->app->bind(CareerRepInterface::class, EloquentCareerRepository::class);
        $this->app->bind(CareerQueryRepInterface::class, EloquentCareerQueryRepository::class);
        $this->app->bind(StudentDetailReInterface::class,EloquentStudentDetailRepository::class);
        $this->app->bind(RefreshTokenRepInterface::class,EloquentRefreshTokenRepository::class);
        $this->app->bind(AccessTokenRepInterface::class, EloquentAccessTokenRepository::class);
        $this->app->bind(RolesAndPermissionsRepInterface::class, EloquentRolesAndPermissionsRepository::class);
        $this->app->bind(DBRepInterface::class, EloquentDBRepository::class);
        $this->app->singleton(CacheService::class, function () {return new CacheService();});
        $this->app->bind(RolesAndPermissosQueryRepInterface::class,EloquentRolesAndPermissionQueryRepository::class);
        $this->app->bind(ParentStudentRepInterface::class, EloquentParentStudentRepository::class);
        $this->app->bind(ParentStudentQueryRepInterface::class, EloquentParentStudentQueryRepository::class);
        $this->app->bind(ParentInviteRepInterface::class, EloquentParentInviteRepository::class);
        $this->app->bind(ParentInviteQueryRepInterface::class, EloquentParentInviteQueryRepository::class);
        $this->app->bind(SemesterPromotionsRepInterface::class, EloquentSemesterPromotionsRepository::class);
        $this->app->bind(PaymentEventRepInterface::class, EloquentPaymentEventRepository::class);
        $this->app->bind(PaymentEventQueryRepInterface::class, EloquentPaymentEventQueryRepository::class);
        $this->app->bind(ReceiptRepInterface::class, EloquentReceiptRepository::class);
        $this->app->bind(NotificationRepInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(NotificationQueryRepInterface::class, EloquentNotificationQueryRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        bcscale(8);
        Carbon::setLocale('es');

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        RateLimiter::for('global', function ($request) {
            return Limit::perMinute(30)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Storage::disk('local')->buildTemporaryUrlsUsing(
            function (string $path, \DateTime $expiration, array $options) {
                return URL::temporarySignedRoute(
                    'files.download',
                    $expiration,
                    [
                        'disk' => 'local',
                        'path' => $path
                    ]
                );
            }
        );

        Storage::disk('public')->buildTemporaryUrlsUsing(
            function (string $path, \DateTime $expiration, array $options) {
                return URL::temporarySignedRoute(
                    'files.download',
                    $expiration,
                    [
                        'disk' => 'public',
                        'path' => $path
                    ]
                );
            }
        );

        Response::macro('success', function ($data = null, $message = null, $status = 200) {
            $payload = ['success' => true];

            if (!is_null($data)) {
                $payload['data'] = $data;
            }

            if (!is_null($message) && $message !== '') {
                $payload['message'] = $message;
            }

            return response()->json($payload, $status);
        });

        Response::macro('error', function ($message = null, $status = 400, $errors = null, ?string $code=null) {
            $payload = ['success' => false];

            if(!is_null($code) && $code !== '') {
                $payload['error_code'] = $code;
            }
            if (!is_null($message) && $message !== '') {
                $payload['message'] = $message;
            }

            if (!is_null($errors) && !empty($errors)) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        });
    }
}
