<?php

namespace App\Services;

use App\Models\CustomCategory;

class CustomCategoryService
{
    public function getAll()
    {
        return CustomCategory::all();
    }

    public function findById($id)
    {
        return CustomCategory::findOrFail($id);
    }

    public function create(array $data)
    {
        return CustomCategory::create($data);
    }

    public function update($id, array $data)
    {
        $category = CustomCategory::findOrFail($id);
        $category->update($data);
        return $category;
    }

    public function delete($id)
    {
        $category = CustomCategory::findOrFail($id);
        return $category->delete();
    }
}
