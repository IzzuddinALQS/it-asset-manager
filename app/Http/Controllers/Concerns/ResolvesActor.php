<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesActor
{
    /**
     * The authenticated user, narrowed to the application's User model.
     */
    private function actor(Request $request): User
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return $user;
    }
}
