<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlantController extends Controller
{
    /**
     * Load all records with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15); // default 15

        $plants = Plant::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('scientific_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        return response()->json($plants);
    }

    /**
     * Save new record
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // add more fields as needed
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'scientific_name', 'description']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('plants', 'public');
        }

        $plant = Plant::create($data);

        return response()->json([
            'message' => 'Plant created successfully',
            'plant'   => $plant
        ], 201);
    }

    /**
     * Update record
     */
    public function update(Request $request, Plant $plant)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'scientific_name', 'description']);

        if ($request->hasFile('image')) {
            // Delete old image if exists (optional)
            if ($plant->image) {
                \Storage::disk('public')->delete($plant->image);
            }
            $data['image'] = $request->file('image')->store('plants', 'public');
        }

        $plant->update($data);

        return response()->json([
            'message' => 'Plant updated successfully',
            'plant'   => $plant->fresh()
        ]);
    }

    /**
     * Delete record
     */
    public function destroy(Plant $plant)
    {
        if ($plant->image) {
            \Storage::disk('public')->delete($plant->image);
        }

        $plant->delete();

        return response()->json([
            'message' => 'Plant deleted successfully'
        ]);
    }
}