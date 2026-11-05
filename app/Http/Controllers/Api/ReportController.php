<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Http\Resources\GrowerReportResource;
use App\Http\Resources\LayerReportResource;
use App\Services\Reports\FlockReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly FlockReportService $reports,
        private readonly ReportExportService $exports,
    ) {}

    public function grower(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $rows = $this->reports->grower($filters);

        return response()->json([
            'filters' => $filters,
            'columns' => array_keys($this->growerColumns()),
            'data' => GrowerReportResource::collection($rows)->resolve(),
            // 'unavailable_fields' => $this->reports->unavailableFields('grower'),
        ]);
    }

    public function layer(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $rows = $this->reports->layer($filters);

        return response()->json([
            'filters' => $filters,
            'columns' => array_keys($this->layerColumns()),
            'data' => LayerReportResource::collection($rows)->resolve(),
            // 'unavailable_fields' => $this->reports->unavailableFields('layer'),
        ]);
    }

    public function exportGrower(ReportFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $rows = $this->reports->grower($filters);

        return $this->download($rows, $this->growerColumns(), 'grower-report', $request->exportFormat());
    }

    public function exportLayer(ReportFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $rows = $this->reports->layer($filters);

        return $this->download($rows, $this->layerColumns(), 'layer-report', $request->exportFormat());
    }

    private function download(array $rows, array $columns, string $fileName, string $format): BinaryFileResponse
    {
        if ($format === 'pdf') {
            $path = $this->exports->pdf($rows, $columns, str($fileName)->headline()->toString());

            return response()
                ->download($path, $fileName . '.pdf', ['Content-Type' => 'application/pdf'])
                ->deleteFileAfterSend();
        }

        $path = $this->exports->xlsx($rows, $columns, $fileName);

        return response()
            ->download($path, $fileName . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    private function growerColumns(): array
    {
        return [
            'date' => 'date',
            'age_week' => 'age_week',
            'population' => 'population',
            'mortality' => 'mortality',
            'culling' => 'culling',
            'mortality_percent' => 'mortality_percent',
            'feed_total_kg' => 'feed_total_kg',
            'feed_gram_per_bird' => 'feed_gram_per_bird',
            'feed_standard_gram_per_bird' => 'feed_standard_gram_per_bird',
            'water_total_l' => 'water_total_l',
            'water_ml_per_bird' => 'water_ml_per_bird',
            'actual_body_weight_g' => 'actual_body_weight_g',
            'standard_body_weight_g' => 'standard_body_weight_g',
            'temperature_c' => 'temperature_c',
            'actual_uniformity_percent' => 'actual_uniformity_percent',
            'standard_uniformity_percent' => 'standard_uniformity_percent',
            'feed_name' => 'feed_name',
            'medicine_vitamin' => 'medicine_vitamin',
            'vaccine' => 'vaccine',
            'notes' => 'notes',
        ];
    }

    private function layerColumns(): array
    {
        return [
            'date' => 'date',
            'age_week' => 'age_week',
            'age_day' => 'age_day',
            'population' => 'population',
            'mortality' => 'mortality',
            'current_population' => 'current_population',
            'feed_total_kg' => 'feed_total_kg',
            'feed_gram_per_bird' => 'feed_gram_per_bird',
            'water_total_l' => 'water_total_l',
            'water_ml_per_bird' => 'water_ml_per_bird',
            'normal_egg_count' => 'normal_egg_count',
            'normal_egg_weight_kg' => 'normal_egg_weight_kg',
            'sorted_egg_count' => 'sorted_egg_count',
            'sorted_egg_weight_kg' => 'sorted_egg_weight_kg',
            'broken_egg_count' => 'broken_egg_count',
            'broken_egg_weight_kg' => 'broken_egg_weight_kg',
            'spoiled_egg_count' => 'spoiled_egg_count',
            'spoiled_egg_weight_kg' => 'spoiled_egg_weight_kg',
            'avg_egg_weight_g' => 'avg_egg_weight_g',
            'egg_weight_per_1000_birds' => 'egg_weight_per_1000_birds',
            'hen_day_production' => 'hen_day_production',
            'fcr' => 'fcr',
            'feed_name' => 'feed_name',
            'medicine_vitamin' => 'medicine_vitamin',
            'vaccine' => 'vaccine',
            'notes' => 'notes',
        ];
    }
}
