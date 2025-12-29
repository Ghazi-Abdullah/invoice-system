<?php

namespace App\Traits;

use App\Constants\Constants;

trait ResponseTrait
{
    /**
     * Success response
     */
    public function successResponse($message, $data = null, $code = Constants::RESPONSE_SUCCESS)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Failure response
     */
    public function failureResponse($message, $errors = null, $code = Constants::RESPONSE_SERVER_ERROR)
    {
        $response = [
            'status' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response
     */
    public function validationErrorResponse($errors)
    {
        return response()->json([
            'status' => false,
            'message' => __('messages.validation_error'),
            'errors' => $errors
        ], Constants::RESPONSE_VALIDATION_ERROR);
    }

    /**
     * Not found response
     */
    public function notFoundResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?: __('messages.not_found')
        ], Constants::RESPONSE_NOT_FOUND);
    }

    /**
     * Unauthorized response
     */
    public function unauthorizedResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?: __('messages.unauthorized')
        ], Constants::RESPONSE_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     */
    public function forbiddenResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?: __('messages.forbidden')
        ], Constants::RESPONSE_FORBIDDEN);
    }

    /**
     * Method not allowed response
     */
    public function methodNotAllowedResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?: __('messages.method_not_allowed')
        ], Constants::RESPONSE_METHOD_NOT_ALLOWED);
    }

    /**
     * Too many requests response
     */
    public function tooManyRequestsResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?: __('messages.too_many_requests')
        ], 429);
    }

    /**
     * Paginated response
     */
    public function paginatedResponse($data, $message = null)
    {
        return response()->json([
            'status' => true,
            'message' => $message ?: __('messages.success'),
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'links' => $data->links(),
            ]
        ], Constants::RESPONSE_SUCCESS);
    }

    /**
     * Success response with custom structure
     */
    public function customSuccessResponse($data = [], $code = Constants::RESPONSE_SUCCESS)
    {
        return response()->json($data, $code);
    }

    /**
     * Error response with custom structure
     */
    public function customErrorResponse($data = [], $code = Constants::RESPONSE_SERVER_ERROR)
    {
        return response()->json($data, $code);
    }
}
