<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\UserInfo;
use App\Models\InviteUser;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Api\BaseController as BaseController;

class AuthController extends BaseController
{
    /**
     * User registration
     * 
     * @param  Request  $request
     * @return $token
     */
    public function register(Request $request)
    {
        DB::beginTransaction();

        logger($request->all());

        if (!$request->has('token_code')) {
            return $this->sendError('Token code is required.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$request->has('user')) {
            return $this->sendError('User data is required.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        $invite = InviteUser::where('token', $request->token_code)->first();

        if ($invite == null) {
            return $this->sendError('Token not found.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->email != $request->user['email']) {
            return $this->sendError('Email not match.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->email_verified_at != null) {
            return $this->sendError('Email already verified.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->token_expired_at < now()) {
            return $this->sendError('Token expired.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->is_active == 0) {
            return $this->sendError('Invite is not active.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        $validated = Validator::make($request->all(), [
            'user.name' => 'required|string',
            'user.email' => 'required|string|unique:users,email',
            'user.password' => 'required|string',
            'user.c_password' => 'required|same:user.password'
        ], [
            'user.email.unique' => 'Email already exists.',
            'user.c_password.same' => 'Password confirmation failed.',
        ]);

        if ($validated->fails()) {
            return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User([
            'name' => $request->user['name'],
            'email' => $request->user['email'],
            'password' => bcrypt($request->user['password']),
            'shipper_id' => $invite->shipper_id,
            'active' => 1,
        ]);

        try {
            if ($user->saveOrFail()) {
                $role = null;

                if ($request->has('role')) {
                    $role = Role::findByName($request->role, 'api');
                } else {
                    $role = Role::findByName('user', 'api');
                }

                $user_info = new UserInfo([
                    'user_id' => $user->id,
                ]);
                $user_info->saveOrFail();

                $user->assignRole($role);

                $invite->email_verified_at = now();
                $invite->token_expired_at = now();
                $invite->is_active = 0;
                $invite->save();

                $tokenResult = $user->createToken(uuid_create());
                $token = $tokenResult->accessToken;

                DB::commit();

                event(new Registered($user));

                return $this->sendResponse([
                    'accessToken' => $token,
                ], 'User created successfully.');
            } else {
                DB::rollBack();
                logger('User not saved');
                return $this->sendError('User creation failed.', [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            logger($e);
            return $this->sendError('Error on create user', [$e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Summary of email_verifiy
     * @param \Illuminate\Foundation\Auth\EmailVerificationRequest $request
     * @return void
     */
    public function email_verifiy(EmailVerificationRequest $request) {
        $request->fulfill();
    }

    /**
     * Login user
     *
     * @param  Request  $request
     * @return $token
     */
    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->user["email"], 'password' => $request->user["password"]])) {
            $user = Auth::user();

            $tokenResult = $user->createToken(uuid_create());
            $token = $tokenResult->accessToken;

            $user['roles'] = $user->getAllPermissions();
            $user['shipper'] = $user->shipper;
            $user['user_info'] = $user->userInfo;

            if ($user['shipper_id'] == null) {
                $user['shipper'] = $this->fake_shipper();
            }

            return $this->sendResponse(['accessToken' => $token, 'user' => auth()->user()], 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }

    /**
     * Verify logged in user
     * 
     * @return user
     */
    public function user()
    {
        $user = auth()->user();
        $user['roles'] = $user->getAllPermissions();
        $user['user_info'] = $user->userInfo;

        $user['shipper'] = ($user['shipper_id'] == null) ? $this->fake_shipper() : $user->shipper;

        return $this->sendResponse(['user' => $user], 'User retrieved successfully.');
    }

    /**
     * Summary of user_permissions
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function userPermissions(Request $request)
    {
        $user = auth()->user();
        $return['user_id'] = $user['id'];
        $return['roles'] = $user->getRoleNames();
        $return['permissions'] = $user->getAllPermissions();
        $return['shipper_id'] = $user['shipper_id'];

        return $this->sendResponse($return, 'User retrieved successfully.');
    }

    /**
     * Summary of verifyToken
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function verifyToken()
    {
        return $this->sendResponse([], 'Token verified successfully.');
    }

    /**
     * refresh token
     *
     * @return void
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        $response = Http::asForm()->post(env('APP_URL') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
            'scope' => '',
        ]);

        if ($response->status == 'error') {
            return $this->sendError('Unauthorized.', [], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user['token'] = $response->json();

        return $this->sendResponse($user, 'Token refreshed successfully.');

    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::user()->tokens()->delete();

        return $this->sendResponse([], 'User logged out successfully.');
    }

    /**
     * Summary of oauth_client
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function oauth_client(Request $request)
    {
        $requestClient = http_build_query([
            // 'grant_type' => 'authorization_code',
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'response_type' => 'code',
            'scope' => 'freight-calculation',
        ]);

        // $tokenRequest = Request::create('/oauth/authorize', 'GET', $requestClient);
        $tokenRequest = Request::create("/oauth/authorize?{$requestClient}", 'GET');
        $response = app()->handle($tokenRequest);

        if ($response->getStatusCode() === 200) {
            // Authentication successful
            return $this->sendResponse($response->getContent(), 'Client credentials retrieved successfully.');
        } else {
            return $this->sendError('Unauthorized.', $response->getContent(), JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Summary of verifySignupToken
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function verifySignupToken(Request $request)
    {
        if (!$request->has('token_code')) {
            return $this->sendError('Token code is required.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        $invite = InviteUser::where('token', $request->token_code)->first();
        if ($invite->email_verified_at != null) {
            return $this->sendError('Email already verified.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->token_expired_at < now()) {
            return $this->sendError('Token expired.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($invite->is_active == 0) {
            return $this->sendError('Invite is not active.', [], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->sendResponse(['email' => $invite->email], 'Signup token is valid.');
    }

    /**
     * Fake shipper
     * 
     * @return [array] shipper
     */
    private function fake_shipper()
    {
        return [
            'name' => 'Hermes Crew',
            'logo_image_url' => '/images/logo.svg',
            'address' => 'Hermes Crew, 123',
            'address_2' => 'Hermes Crew, 123 Fake Street, London, E1 6QR',
            'city' => 'London',
            'postcode' => 'E1 6QR',
            'country' => 'United Kingdom',
            'phone' => '020 1234 5678',
            'contact_name' => 'Hermes Crew',
            'contact_email' => 'teste@hermes.com',
        ];
    }
}