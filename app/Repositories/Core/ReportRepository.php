<?php

namespace App\Repositories\Core;

use App\Models\Report;

class ReportRepository
{
    public function all()
    {
        return Report::paginate(10);
    }

    public function findById(string $id): ?Report
    {
        return Report::find($id);
    }

    public function create(array $data): Report
    {
        $report = new Report();
        $report->trip_id = $data['trip_id'] ?? null;
        $report->description = $data['description'] ?? null;
        $report->type = $data['type'] ?? null;

        $report->public_id = uuid();

        $report->save();
        return $report;
    }

    public function update(string $id, array $data): ?Report
    {
        $report = $this->findById($id);

        if (!$report) {
            return null;
        }

        $report->trip_id = $data['trip_id'] ?? $report->trip_id;
        $report->description = $data['description'] ?? $report->description;
        $report->type = $data['type'] ??  $report->type;

        $report->save();
        return $report;
    }

    public function delete(string $id): bool
    {
        $report = Report::find($id);
        if ($report) {
            $report->delete();
            return true;
        }
        return false;
    }
}
