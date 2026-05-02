<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });
    }

    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ModelNotFoundException) {
            abort(404, class_basename($e->getModel()).' not found.');
        }

        if ($e instanceof SSHError) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', $e->getLog()?->getContent(30) ?? $e->getMessage());
            }

            return response()->json(['error' => $e->getLog()?->getContent(30) ?? $e->getMessage()], 500);
        }

        if ($e instanceof AuthorizationException) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', __('You don\'t have permission to perform this action.'));
            }
        }

        if ($e instanceof AppError) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', $e->getMessage());
            }

            return response()->json(['error' => $e->getMessage()], 500);
        }

        return parent::render($request, $e);
    }
}
