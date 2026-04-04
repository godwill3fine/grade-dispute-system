<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $identifier = $this->resolveIdentifier($request);

        if (! $identifier) {
            return response()->json(['error' => 'User identifier missing'], 401);
        }

        $user = User::where('identifier', $identifier)->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->attributes->set('actor_identifier', $user->identifier);
        $request->attributes->set('actor_role', $user->role);
        $request->attributes->set('actor_user_id', $user->id);

        return $next($request);
    }

    private function resolveIdentifier(Request $request): ?string
    {
        $candidateIdentifiers = [
            $request->user()?->identifier,
            $request->header('X-User-Identifier'),
            $request->input('identifier'),
            $request->input('performed_by'),
            $request->input('student_id'),
            $request->input('staff_id'),
            $request->input('assigned_to'),
            $request->route('student_id'),
            $request->route('lecturer_id'),
            $request->route('staff_id'),
            $request->route('userId'),
        ];

        foreach ($candidateIdentifiers as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $trimmed = trim($candidate);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }
}
