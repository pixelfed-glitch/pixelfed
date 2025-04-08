<?php

namespace App\Http\Controllers;

use App\Models\CustomFilter;
use App\Models\CustomFilterKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomFilterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = CustomFilter::where('profile_id', $request->user()->profile_id)
            ->unexpired()
            ->with(['keywords', 'statuses'])
            ->get();

        return response()->json([
            'filters' => $filters,
        ]);
    }

    public function show(CustomFilter $filter)
    {
        $this->authorize('view', $filter);

        $filter->load(['keywords', 'statuses']);

        return response()->json([
            'filter' => $filter,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'context' => 'required|array',
            'context.*' => 'string|in:'.implode(',', CustomFilter::VALID_CONTEXTS),
            'filter_action' => 'integer|in:0,1,2',
            'expires_in' => 'nullable|integer|min:0',
            'irreversible' => 'boolean',
            'keywords' => 'array',
            'keywords.*.keyword' => 'required|string',
            'keywords.*.whole_word' => 'boolean',
            'status_ids' => 'array',
            'status_ids.*' => 'integer|exists:statuses,id',
        ]);

        DB::beginTransaction();

        try {
            $expiresAt = null;
            if (isset($validatedData['expires_in']) && $validatedData['expires_in'] > 0) {
                $expiresAt = now()->addSeconds($validatedData['expires_in']);
            }

            $filter = CustomFilter::create([
                'phrase' => $validatedData['title'],
                'context' => $validatedData['context'],
                'action' => $validatedData['filter_action'] ??
                           (isset($validatedData['irreversible']) && $validatedData['irreversible'] ?
                            CustomFilter::ACTION_HIDE : CustomFilter::ACTION_WARN),
                'expires_at' => $expiresAt,
                'profile_id' => $request->user()->profile_id,
            ]);

            if (isset($validatedData['keywords'])) {
                foreach ($validatedData['keywords'] as $keywordData) {
                    $filter->keywords()->create([
                        'keyword' => $keywordData['keyword'],
                        'whole_word' => $keywordData['whole_word'] ?? true,
                    ]);
                }
            }

            if (isset($validatedData['status_ids'])) {
                foreach ($validatedData['status_ids'] as $statusId) {
                    $filter->statuses()->create([
                        'status_id' => $statusId,
                    ]);
                }
            }

            DB::commit();

            $filter->load(['keywords', 'statuses']);

            return response()->json([
                'filter' => $filter,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create filter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, CustomFilter $filter)
    {
        $this->authorize('update', $filter);

        $validatedData = $request->validate([
            'title' => 'string',
            'context' => 'array',
            'context.*' => 'string|in:'.implode(',', CustomFilter::VALID_CONTEXTS),
            'filter_action' => 'integer|in:0,1,2',
            'expires_in' => 'nullable|integer|min:0',
            'irreversible' => 'boolean',
            'keywords' => 'array',
            'keywords.*.id' => 'nullable|exists:custom_filter_keywords,id',
            'keywords.*.keyword' => 'required|string',
            'keywords.*.whole_word' => 'boolean',
            'keywords.*._destroy' => 'boolean',
            'status_ids' => 'array',
            'status_ids.*' => 'integer|exists:statuses,id',
        ]);

        DB::beginTransaction();

        try {
            if (isset($validatedData['expires_in'])) {
                if ($validatedData['expires_in'] > 0) {
                    $filter->expires_at = now()->addSeconds($validatedData['expires_in']);
                } else {
                    $filter->expires_at = null;
                }
            }

            if (isset($validatedData['title'])) {
                $filter->phrase = $validatedData['title'];
            }

            if (isset($validatedData['context'])) {
                $filter->context = $validatedData['context'];
            }

            if (isset($validatedData['filter_action'])) {
                $filter->action = $validatedData['filter_action'];
            } elseif (isset($validatedData['irreversible'])) {
                $filter->irreversible = $validatedData['irreversible'];
            }

            $filter->save();

            if (isset($validatedData['keywords'])) {
                $existingKeywordIds = $filter->keywords->pluck('id')->toArray();
                $processedIds = [];

                foreach ($validatedData['keywords'] as $keywordData) {
                    if (isset($keywordData['id']) && isset($keywordData['_destroy']) && $keywordData['_destroy']) {
                        CustomFilterKeyword::destroy($keywordData['id']);

                        continue;
                    }

                    if (isset($keywordData['id']) && in_array($keywordData['id'], $existingKeywordIds)) {
                        $keyword = CustomFilterKeyword::find($keywordData['id']);
                        $keyword->update([
                            'keyword' => $keywordData['keyword'],
                            'whole_word' => $keywordData['whole_word'] ?? $keyword->whole_word,
                        ]);
                        $processedIds[] = $keywordData['id'];
                    } else {
                        $newKeyword = $filter->keywords()->create([
                            'keyword' => $keywordData['keyword'],
                            'whole_word' => $keywordData['whole_word'] ?? true,
                        ]);
                        $processedIds[] = $newKeyword->id;
                    }
                }

                $keywordsToDelete = array_diff($existingKeywordIds, $processedIds);
                if (! empty($keywordsToDelete)) {
                    CustomFilterKeyword::destroy($keywordsToDelete);
                }
            }

            if (isset($validatedData['status_ids'])) {
                $filter->statuses()->delete();

                foreach ($validatedData['status_ids'] as $statusId) {
                    $filter->statuses()->create([
                        'status_id' => $statusId,
                    ]);
                }
            }

            DB::commit();

            $filter->load(['keywords', 'statuses']);

            return response()->json([
                'filter' => $filter,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update filter',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(CustomFilter $filter)
    {
        $this->authorize('delete', $filter);

        $filter->delete();

        return response()->json(null, 204);
    }
}
