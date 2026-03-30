<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCityRequest;
use App\Http\Requests\Api\UpdateCityRequest;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        $cities = City::with('country')
            ->orderBy('name_en')
            ->get();

        return response()->json($cities);
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $city = City::create($request->validated());

        return response()->json($city->load('country'), 201);
    }

    public function show(City $city): JsonResponse
    {
        return response()->json($city->load('country'));
    }

    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        $city->update($request->validated());

        return response()->json($city->fresh('country'));
    }

    public function destroy(City $city): JsonResponse
    {
        $city->delete();

        return response()->json(null, 204);
    }
}
