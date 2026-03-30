<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHotelRequest;
use App\Http\Requests\Api\UpdateHotelRequest;
use App\Models\City;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;

class CityHotelController extends Controller
{
    public function index(City $city): JsonResponse
    {
        $hotels = $city->hotels()
            ->with('category', 'currency')
            ->orderBy('name_en')
            ->get();

        return response()->json($hotels);
    }

    public function store(StoreHotelRequest $request, City $city): JsonResponse
    {
        $hotel = $city->hotels()->create($request->validated());

        return response()->json($hotel->load('category', 'currency'), 201);
    }

    public function show(City $city, Hotel $hotel): JsonResponse
    {
        abort_if($hotel->city_id !== $city->id, 404);

        return response()->json($hotel->load('category', 'currency'));
    }

    public function update(UpdateHotelRequest $request, City $city, Hotel $hotel): JsonResponse
    {
        abort_if($hotel->city_id !== $city->id, 404);

        $hotel->update($request->validated());

        return response()->json($hotel->fresh('category', 'currency'));
    }

    public function destroy(City $city, Hotel $hotel): JsonResponse
    {
        abort_if($hotel->city_id !== $city->id, 404);

        $hotel->delete();

        return response()->json(null, 204);
    }
}
