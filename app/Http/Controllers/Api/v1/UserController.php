<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserAllRequest;
use App\Http\Transformers\UserTransformer;

class UserController extends Controller
{
    public function all(UserAllRequest $request)
    {
        $users = User::orderBy('created_at', 'desc')->limit(50)->get();

        $this->respondWithPaginatedCollection($users, $request, new UserTransformer);
    }
}
