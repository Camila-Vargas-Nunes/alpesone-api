<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegratorData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class IntegratorDataController extends Controller
{
    /**
     * Display a listing of the resource with pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $perPage = min($perPage, 100); // Limit max per page to 100

        $data = IntegratorData::orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'source_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $dataHash = md5(json_encode($request->data));
        
        // Check if data already exists
        if (IntegratorData::where('data_hash', $dataHash)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Data already exists'
            ], 409);
        }

        $integratorData = IntegratorData::create([
            'data' => $request->data,
            'data_hash' => $dataHash,
            'last_updated' => now(),
            'source_url' => $request->source_url
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data created successfully',
            'data' => $integratorData
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $integratorData = IntegratorData::find($id);

        if (!$integratorData) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $integratorData
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $integratorData = IntegratorData::find($id);

        if (!$integratorData) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'data' => 'sometimes|array',
            'source_url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['source_url']);
        
        if ($request->has('data')) {
            $updateData['data'] = $request->data;
            $updateData['data_hash'] = md5(json_encode($request->data));
            $updateData['last_updated'] = now();
        }

        $integratorData->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully',
            'data' => $integratorData->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $integratorData = IntegratorData::find($id);

        if (!$integratorData) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        $integratorData->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data deleted successfully'
        ]);
    }

    /**
     * Get the latest data from the integrator.
     *
     * @return JsonResponse
     */
    public function latest(): JsonResponse
    {
        $latestData = IntegratorData::getLatest();

        if (!$latestData) {
            return response()->json([
                'success' => false,
                'message' => 'No data available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $latestData
        ]);
    }
}
