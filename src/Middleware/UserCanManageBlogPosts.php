<?php

namespace HessamCMS\Middleware;

use Closure;

/**
 * Class UserCanManageBlogPosts
 * @package HessamCMS\Middleware
 */
class UserCanManageBlogPosts
{

    /**
     * Show 401 error if \Auth::user()->canManageHessamCMSPosts() == false
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!\Auth::check()) {
            abort(401,"User not authorised to manage blog posts: You are not logged in");
            return redirect('/login');
        }
        if (!\Auth::user()->canManageHessamCMSPosts()) {
            abort(401,"User not authorised to manage blog posts: Your account is not authorised to edit blog posts");
        }
        return $next($request);
    }
}
