<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const GAME_STATUS = ['EN_PROCESO', 'FINALIZADO'];
    const BOX_GAME = [1, 2, 3, 4, 5, 6, 7, 8, 9];
    const MARKS = ['first' => 'X', 'second' => 'O'];
    const POSSIBILITIES = [
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9],
        [1, 4, 7],
        [2, 5, 8],
        [3, 6, 9],
        [1, 5, 9],
        [3, 5, 7]
    ];

    /**
     * get specific data from error
     * @param \Exception $e
     * @return string
     */
    protected function customException(\Exception $e)
    {
        return $e->getMessage() . ' On: ' . $e->getFile() . ' Line: ' . $e->getLine();
    }

    /**
     * save log
     * @param \Exception $e
     * @param $result
     * @param null $channel
     */
    protected function logRecord(\Exception $e, &$result, $channel = null): void
    {
        (!isset($channel)) ? info(self::customException($e)) : Log::channel($channel)->info(self::customException($e));
        $customMessage = $e->getMessage();
        $customCode = $e->getCode();
        $customCode = (isset($customCode) && isset(Response::$statusTexts[$customCode])) ? $customCode : Response::HTTP_INTERNAL_SERVER_ERROR;
        $result->message = (isset($customMessage) && !empty($customMessage && isset(Response::$statusTexts[$customCode]))) ? $customMessage : "Internal Server Error";
        $result->code = $customCode;
    }

    protected function getErrorsInMessage(array $errors): string
    {
        return "" . implode(', ', $errors);
    }
}
