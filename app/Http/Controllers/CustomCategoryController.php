<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CustomCategoryService;
use App\Models\CustomCategory;

class CustomCategoryController extends Controller
{
    protected $service;

    public function __construct(CustomCategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'isActive' => 'boolean',
        ]);

        $created = $this->service->create($validated);
        return response()->json($created, 201);
    }

    public function show($id)
    {
        $item = $this->service->findById($id);
        return response()->json($item);
    }

    public function active()
    {
        $activeCategories = CustomCategory::where('isActive', true)->get();
        return response()->json($activeCategories);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|string|max:255',
            'isActive' => 'boolean',
        ]);

        $updated = $this->service->update($id, $validated);
        return response()->json($updated);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
