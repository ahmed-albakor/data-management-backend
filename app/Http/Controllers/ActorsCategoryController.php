<?php

namespace App\Http\Controllers;

use App\Models\ActorsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActorsCategoryController extends Controller
{
    // عرض كل الفئات
    public function index(Request $request)
    {
        // جلب جميع الفئات بدون تقسيم
        $categories = ActorsCategory::all();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ], 200);
    }

    // عرض فئة معينة
    public function show($id)
    {
        $category = ActorsCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ], 200);
    }

    // إنشاء فئة جديدة
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = ActorsCategory::create($request->only('name', 'description'));

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الفئة بنجاح',
            'data' => $category,
        ], 201);
    }

    // تعديل فئة
    public function update(Request $request, $id)
    {
        $category = ActorsCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update($request->only('name', 'description'));

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الفئة بنجاح',
            'data' => $category,
        ], 200);
    }

    // حذف فئة
    public function destroy($id)
    {
        $category = ActorsCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفئة بنجاح',
        ], 200);
    }
}
