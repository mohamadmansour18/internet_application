<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait AspectTrait
{
    protected function around(
        string $action ,
        array $context ,
        callable $callback ,
        callable $before = null ,
        callable $after = null,
        callable $onError = null ,
        callable $audit = null ,
        bool $withTiming = false ,
        bool $withLogging = false ,
    ){
        $start = $withTiming ? microtime(true) : null ;

        try {
            if($withLogging)
            {
                Log::channel('aspect')->info("[$action] BEFORE" , $context);
            }

            if($before)
            {
                $before();
            }

            $result = $callback();

            if($audit)
            {
                $payload = $audit($result);
                if($payload)
                {
                    AuditLog::query()->create([
                        'actor_id'     => $payload['actor_id'] ?? Auth::id(),
                        'subject_type' => $payload['subject_type'] ?? null,
                        'subject_id'   => $payload['subject_id'] ?? null,
                        'changes'      => $payload['changes'] ?? [],
                    ]);
                }
            }

            if($after)
            {
                $after($result);
            }

            if($withTiming && $withLogging)
            {
                $duration = microtime(true) - $start;
                $context['timeExecutionInMicroSecond'] = $duration * 1000;
            }

            if($withLogging)
            {
                Log::channel('aspect')->info("[$action] AFTER", $context);
            }

            return $result;
        }catch(\Throwable $exception)
        {
            if($withLogging)
            {
                Log::channel('aspect')->error("[$action] EXCEPTION", array_merge($context , [
                    'error' => $exception->getMessage(),
                ]));
            }
            if($onError)
            {
                $onError($exception);
            }
            throw $exception;
        }
    }
}
