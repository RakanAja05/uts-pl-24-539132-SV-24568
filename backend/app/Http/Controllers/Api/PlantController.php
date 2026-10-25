<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;

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
        $plant = Plant::create($request->validate($this->rules()));
        return response()->json($plant, 201);
    }

    public function update(Request $request, Plant $plant)
    {
        $plant->update($request->validate($this->rules()));
        return response()->json($plant);
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();
        return response()->json(['message' => 'Data dihapus']);
    }
}
