<?php

// File: app/Http/Controllers/Api/TourTemplateController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTourTemplateRequest;
use App\Http\Requests\Api\UpdateTourTemplateRequest;
use App\Models\TourTemplate;
use App\Services\TourTemplateService;
use Illuminate\Http\JsonResponse;

class TourTemplateController extends Controller
{
    public function __construct(
        private readonly TourTemplateService $service,
    ) {}

    public function index(): JsonResponse
    {
        $templates = TourTemplate::with(['departureCity', 'stays.city', 'legs'])
            ->latest()
            ->get();

        return response()->json($templates);
    }

    public function store(StoreTourTemplateRequest $request): JsonResponse
    {
        $template = $this->service->create($request->validated());

        return response()->json($template, 201);
    }

    public function show(TourTemplate $tourTemplate): JsonResponse
    {
        $summary = $this->service->summary($tourTemplate);

        return response()->json($summary->toArray());
    }

    public function update(UpdateTourTemplateRequest $request, TourTemplate $tourTemplate): JsonResponse
    {
        $template = $this->service->update($tourTemplate, $request->validated());

        return response()->json($template);
    }

    public function destroy(TourTemplate $tourTemplate): JsonResponse
    {
        $tourTemplate->delete();

        return response()->json(null, 204);
    }
}
