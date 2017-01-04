<?php

namespace App\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * Related models to include in this transformation.
     *
     * @var array
     */
    protected $defaultIncludes = [
    ];

    /**
     * Turn this item object into a generic array.
     *
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'uuid' => (string) $user->uuid,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'avatar' => (string) $user->avatar,
        ];
    }
}
