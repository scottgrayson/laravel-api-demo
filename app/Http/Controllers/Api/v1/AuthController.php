<?php

namespace App\Http\Controllers\Api\v1;

use Auth;
use JWTAuth;
use Socialite;
use JWTFactory;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Requests\AuthRegisterRequest;
use Illuminate\Auth\Access\AuthorizationException;

class AuthController extends Controller
{
    public function register(AuthRegisterRequest $request)
    {
        $user = new User;
        $user->username = $request->get('username');
        $user->password = \Hash::make($request->get('password'));
        $user->save();

        return $this->respondWithItem($user, new UserTransformer, ['authorization' => $token]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if ( ! $token = JWTAuth::attempt($credentials)) {
            return $this->errorWrongArgs('Invalid Credentials.');
        }

        $user = Auth::user();

        return $this->respondWithItem($user, new UserTransformer, ['authorization' => $token]);
    }

    /*
    public function loginOther(Request $request)
    {
        $user = User::find($request->get('id'));

        if ( ! $token = JWTAuth::fromUser($user)) {
            return response([
                'status' => 'error',
                'error' => 'invalid.credentials',
                'msg' => 'Invalid Credentials.'
            ], 404);
        }

        return response([
            'status' => 'success'
        ])
        ->header('Authorization', $token);
    }
     */

    public function user(Request $request)
    {
        $user = User::find(Auth::user()->id);

        return $this->respondWithItem($user, new UserTransformer, ['authorization' => $token]);
    }

    public function logout()
    {
        JWTAuth::invalidate();

        return $this->respondWithMessage('logged out');
    }

    public function facebook(Request $request)
    {
        return $this->_social($request, 'facebook', function ($user) {
            return (object) [
                'id' => $user->id,
                'email' => $user->user['email'],
                'first_name' => $user->user['first_name'],
                'last_name' => $user->user['last_name'],
                'avatar' => $user->avatar . '&width=1200'
            ];
        });
    }

    public function google(Request $request)
    {
        return $this->_social($request, 'google', function ($user) {
            return (object) [
                'id' => $user->id,
                'email' => $user['emails'][0]['value'],
                'first_name' => $user['name']['givenName'],
                'last_name' => $user['name']['familyName'],
                'avatar' => array_get($user, 'image')['url'] . '&width=1200'
            ];
        });
    }

    public function buffer(Request $request)
    {
        return $this->_social($request, 'buffer', function ($user) {
            return (object) [
                'id' => $user->id,
                'first_name' => $user->name,
                'last_name' => null,
                'email' => $user->email ?: null,
                'photo_url' => $user->avatar,
            ];
        });
    }

    /*
     * Generic social login
     * Redirect and callback both happen here
     */
    private function _social(Request $request, $provider, $cb)
    {
        if ($request->has('code')) {
            //redirect has already happened. use code to find or create user

            $social_user = Socialite::with($provider)->stateless()->user();
            $social_user = $cb($social_user);

            if (!isset($social_user->id)) {
                return $this->errorInternalError('There was an error getting the ' + $provier + ' user.');
            }

            $user = $this->findOrCreateUser($social_user);

            $user = User::where($provider . '_id', $social_user->id)->first();

            if ( ! ($user instanceof User)) {
                $user = User::where('email', $social_user->email)->first();

                if ( ! ($user instanceof User)) {
                    $user = new User();
                }

                $user->{$provider . '_id'} = $social_user->id;
            }

            // Update info and save.

            if (empty($user->email)) { $user->email = $social_user->email; }
            if (empty($user->name)) { $user->name = $social_user->name; }
            if (empty($user->name)) { $user->avatar = $social_user->avatar; }

            if ( ! $token = JWTAuth::fromUser($user)) {
                throw new AuthorizationException;
            }

            return $this->respondWithItem($user, new UserTransformer, ['authorization' => $token]);
        }

        // redirect if no code it included
        return [
            'token_url' => Socialite::with($provider)->stateless()->redirect()->getTargetUrl()
        ];
    }

    public function refresh()
    {
        return $this->respondWithMessage('refreshed token');
    }
}
