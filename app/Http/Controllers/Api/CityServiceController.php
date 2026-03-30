<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdditionalServiceRequest;
use App\Http\Requests\Api\UpdateAdditionalServiceRequest;
use App\Models\AdditionalService;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityServiceController extends Controller
{
    public function index(City $city): JsonResponse
    {
        $services = $city->additionalServices()
            ->with('currency')
            ->orderBy('name_en')
            ->get();

        return response()->json($services);
    }

    public function store(StoreAdditionalServiceRequest $request, City $city): JsonResponse
    {
        $service = $city->additionalServices()->create($request->validated());

        return response()->json($service->load('currency'), 201);
    }

    public function show(City $city, AdditionalService $service): JsonResponse
    {
        abort_if($service->city_id !== $city->id, 404);

        return response()->json($service->load('currency'));
    }

    public function update(UpdateAdditionalServiceRequest $request, City $city, AdditionalService $service): JsonResponse
    {
        abort_if($service->city_id !== $city->id, 404);

        $service->update($request->validated());

        return response()->json($service->fresh('currency'));
    }

    public function destroy(City $city, AdditionalService $service): JsonResponse
    {
        abort_if($service->city_id !== $city->id, 404);

        $service->delete();

        return response()->json(null, 204);
    }
}
