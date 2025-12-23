<?php

namespace App\Traits;

trait ResponseTrait
{
    public function successResponse($message, $data = null, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function failureResponse($message, $data = null, $code = 422)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function validationErrorResponse($validator)
    {
        return $this->failureResponse(
            $validator->errors()->first(),
            $validator->errors(),
            422
        );
    }
}
