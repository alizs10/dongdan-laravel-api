<?php

namespace App\Http\Controllers\App\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Personal\CreateCategoryRequest;
use App\Http\Requests\Personal\UpdateCategoryRequest;
use App\Models\PersonalCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Fetch all categories for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $categories = PersonalCategory::where('user_id', $request->user()->id)
            ->select('id', 'name', 'created_at', 'updated_at')
            ->withCount('transactions')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'دسته‌بندی‌ها با موفقیت دریافت شدند',
            'data' => $categories,
        ], 200);
    }

    /**
     * Fetch a single category by ID for the authenticated user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $category = PersonalCategory::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->select('id', 'name', 'created_at', 'updated_at')
            ->withCount('transactions')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'message' => 'دسته‌بندی با موفقیت دریافت شد',
            'data' => $category,
        ], 200);
    }

    /**
     * Create a new category for the authenticated user.
     *
     * @param CreateCategoryRequest $request
     * @return JsonResponse
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $category = PersonalCategory::create([
            'name' => $request->validated()['name'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'دسته‌بندی با موفقیت ایجاد شد',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'transactions_count' => 0,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ],
        ], 201);
    }

    /**
     * Update an existing category for the authenticated user.
     *
     * @param UpdateCategoryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = PersonalCategory::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->withCount('transactions')
            ->firstOrFail();

        $category->update([
            'name' => $request->validated()['name'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'دسته‌بندی با موفقیت به‌روزرسانی شد',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'transactions_count' => $category->transactions_count,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ],
        ], 200);
    }

    /**
     * Delete a category for the authenticated user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = PersonalCategory::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'دسته‌بندی با موفقیت حذف شد',
        ], 200);
    }
}
