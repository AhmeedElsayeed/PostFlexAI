<?php

namespace App\Http\Controllers;

use App\Models\CustomSentimentWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomSentimentWordController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomSentimentWord::query();

        if ($request->has('sentiment')) {
            $query->where('sentiment', $request->sentiment);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $words = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $words
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'word' => 'required|string|max:255|unique:custom_sentiment_words',
            'sentiment' => 'required|in:positive,negative',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $word = CustomSentimentWord::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الكلمة بنجاح',
            'data' => $word
        ], 201);
    }

    public function show(CustomSentimentWord $word)
    {
        return response()->json([
            'success' => true,
            'data' => $word
        ]);
    }

    public function update(Request $request, CustomSentimentWord $word)
    {
        $validator = Validator::make($request->all(), [
            'word' => 'string|max:255|unique:custom_sentiment_words,word,' . $word->id,
            'sentiment' => 'in:positive,negative',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $word->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الكلمة بنجاح',
            'data' => $word
        ]);
    }

    public function destroy(CustomSentimentWord $word)
    {
        $word->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الكلمة بنجاح'
        ]);
    }

    public function toggleStatus(CustomSentimentWord $word)
    {
        $word->update(['is_active' => !$word->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير حالة الكلمة بنجاح',
            'data' => $word
        ]);
    }

    public function getCategories()
    {
        $categories = CustomSentimentWord::distinct()->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'words' => 'required|array',
            'words.*.word' => 'required|string|max:255',
            'words.*.sentiment' => 'required|in:positive,negative',
            'words.*.category' => 'nullable|string|max:255',
            'words.*.description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($request->words as $wordData) {
            try {
                CustomSentimentWord::create($wordData);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تم استيراد $imported كلمة بنجاح، وتم تخطي $skipped كلمة",
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped
            ]
        ]);
    }
} 