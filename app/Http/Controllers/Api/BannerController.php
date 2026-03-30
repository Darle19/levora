<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBannerRequest;
use App\Http\Requests\Api\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Banner::with('city')->ordered();

        if ($request->has('city_id')) {
            $cityId = $request->integer('city_id');
            // Return banners for this city + global banners
            $query->where(function ($q) use ($cityId) {
                $q->where('city_id', $cityId)->orWhereNull('city_id');
            });
        }

        return response()->json($query->get());
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = Banner::create($request->validated());

        return response()->json($banner->load('city'), 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        return response()->json($banner->load('city'));
    }

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $banner->update($request->validated());

        return response()->json($banner->fresh('city'));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(null, 204);
    }
}
