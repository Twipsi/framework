<?php
declare(strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Bridge\Auth;

use App\Models\Authentication\User;
use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface as Response;
use Twipsi\Components\Http\Response\JsonResponse;
use Twipsi\Facades\Redirect;
use Twipsi\Facades\Translate;
use Twipsi\Support\Chronos;

trait ValidatesAccounts
{
    /**
     * Check if the account is all valid.
     *
     * @param HttpRequest $request
     * @param User $user
     * @return Response|bool
     */
    protected function validateAccount(HttpRequest $request, User $user): Response|bool
    {
        // If the user is suspended abort.
        if($this->isSuspended($user)) {
            return $this->abortWithSuspendedResponse($request, $user);
        }

        // If the user is deactivated abort.
        return $this->isDeactivated($user)
            ? $this->abortWithDeactivatedResponse($request, $user)
            : true;
    }

    /**
     * Check if the user account is suspended.
     * 
     * @param User $user
     * @return bool
     */
    protected function isSuspended(User $user): bool
    {
        if(property_exists($user, 'suspended_until')) {
 
            $days = Chronos::date()
                    ->travel($user->suspended_until)
                    ->differenceInDays();

            if($days <= 0) {
                $this->releaseSuspension($user);
            }

            return $days > 0;
        }

        return false;
    }

    /**
     * Check if the user account is deactivated.
     * 
     * @param User $user
     * @return bool
     */
    protected function isDeactivated(User $user): bool
    {
        return property_exists($user, 'deactivated_at') && !is_null($user->deactivated_at);
    }

    /**
     * Release the suspension fo a user.
     * 
     * @param User $user
     * @return void
     */
    protected function releaseSuspension(User $user): void
    {
        $user->set('suspended_until', null)->save();
    }

    /**
     * Abort with suspended validation error.
     *
     * @param HttpRequest $request
     * @param User $user
     * @return Response
     */
    protected function abortWithSuspendedResponse(HttpRequest $request, User $user): Response 
    {
        $days = Chronos::date()->travel($user->suspended_until)->differenceInDays();

        $this->logout($request);

        $message = Translate::get('authentication.suspended', [
            'days' => $days,
        ]);

        return $request->isRequestAjax() 
            ? new JsonResponse($message, 403)
            : Redirect::back()->withFlash(['message' => $message]);
    }

    /**
     * Abort with deactivated validation error.
     *
     * @param HttpRequest $request
     * @param User $user
     * @return Response
     */
    protected function abortWithDeactivatedResponse(HttpRequest $request, User $user): Response 
    {
        $date = Chronos::date($user->deactivated_at)->getDateTime();

        $this->logout($request);

        $message = Translate::get('authentication.deactivated', [
            'date' => $date,
        ]);

        return $request->isRequestAjax() 
            ? new JsonResponse($message, 403)
            : Redirect::back()->withFlash(['message' => $message]);
    }
}
