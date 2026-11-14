<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlantController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'watering_interval_days' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }

    public function index()
    {
        return response()->json(Plant::latest()->get());
    }

    public function show(Plant $plant)
    {
        return response()->json($plant);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            Log::warning('Plant creation validation failed', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'store_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'reason' => 'Validation error',
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                    'errors' => $validator->errors()->toArray(),
                ],
            ]);

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plant = Plant::create($validator->validated());

            Log::info('Plant created successfully', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'store_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'plant_id' => $plant->id,
                    'name' => $plant->name,
                ],
            ]);

            return response()->json($plant, 201);
        } catch (\Throwable $e) {
            Log::error('System error: Failed to create plant in database', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'store_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'reason' => 'Database exception or transaction failure',
                    'error_message' => $e->getMessage(),
                    'exception' => get_class($e),
                ],
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function update(Request $request, Plant $plant)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            Log::warning('Plant update validation failed', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'update_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'plant_id' => $plant->id,
                    'reason' => 'Validation error',
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                    'errors' => $validator->errors()->toArray(),
                ],
            ]);

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $plant->update($validator->validated());

            Log::info('Plant updated successfully', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'update_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'plant_id' => $plant->id,
                    'name' => $plant->name,
                ],
            ]);

            return response()->json($plant);
        } catch (\Throwable $e) {
            Log::error('System error: Failed to update plant in database', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'update_plant',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'plant_id' => $plant->id,
                    'reason' => 'Database exception or transaction failure',
                    'error_message' => $e->getMessage(),
                    'exception' => get_class($e),
                ],
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function destroy(Plant $plant)
    {
        try {
            $plantId = $plant->id;
            $plantName = $plant->name;
            $plant->delete();

            Log::info('Plant deleted successfully', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => request()->ip(),
                ],
                'what' => [
                    'action' => 'destroy_plant',
                    'endpoint' => request()->path(),
                ],
                'why' => [
                    'plant_id' => $plantId,
                    'name' => $plantName,
                ],
            ]);

            return response()->json(['message' => 'Data dihapus']);
        } catch (\Throwable $e) {
            Log::error('System error: Failed to delete plant from database', [
                'who' => [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => request()->ip(),
                ],
                'what' => [
                    'action' => 'destroy_plant',
                    'endpoint' => request()->path(),
                ],
                'why' => [
                    'plant_id' => $plant->id,
                    'reason' => 'Database exception or transaction failure',
                    'error_message' => $e->getMessage(),
                    'exception' => get_class($e),
                ],
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
