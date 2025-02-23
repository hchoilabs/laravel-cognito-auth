<?php

namespace Hchoilabs\LaravelCognitoAuth\Auth;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Hchoilabs\LaravelCognitoAuth\Exceptions\NoLocalUserException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Foundation\Auth\AuthenticatesUsers as BaseAuthenticatesUsers;

trait AuthenticatesUsers
{
    use BaseAuthenticatesUsers;

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        try {
            $response = $this->guard()->attempt($this->credentials($request), $request->has('remember'));
        } catch (NoLocalUserException $e) {
            $response = $this->createLocalUser($this->credentials($request));
        }

        return $response;
    }

    /**
     * Create a local user if one does not exist.
     *
     * @param  array  $credentials
     * @return mixed
     */
    protected function createLocalUser($credentials)
    {
        return true;
    }

    /**
     * @param Request $request
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        try {
            if ($this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            }
        } catch (CognitoIdentityProviderException $c) {
            return $this->sendFailedCognitoResponse($c);
        } catch (\Exception $e) {
            return $this->sendFailedLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * @param CognitoIdentityProviderException $exception
     */
    private function sendFailedCognitoResponse(CognitoIdentityProviderException $exception)
    {
        throw ValidationException::withMessages([
            $this->username() => $exception->getAwsErrorMessage(),
        ]);
    }
}
