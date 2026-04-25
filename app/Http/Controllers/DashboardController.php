<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::query()
            ->with(['analysis', 'occurrences'])
            ->withCount('occurrences');

        // Apply filters
        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $incidents = $query->latest()->paginate(25);

        return view('dashboard.index', [
            'incidents' => $incidents,
            'services' => Incident::distinct()->pluck('service'),
        ]);
    }

    public function show(Incident $incident)
    {
        $incident->load(['analysis', 'occurrences' => fn ($q) => $q->latest()]);

        return view('dashboard.show', [
            'incident' => $incident,
        ]);
    }

    public function recurring(Request $request)
    {
        $query = Incident::query()
            ->with('analysis')
            ->withCount('occurrences')
            ->having('occurrences_count', '>=', 1); // 1+ occurrences means 2+ total

        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $incidents = $query->orderByDesc('occurrences_count')->paginate(25);

        return view('dashboard.recurring', [
            'incidents' => $incidents,
            'services' => Incident::distinct()->pluck('service'),
        ]);
    }

    public function update(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => 'required|in:open,investigating,resolved',
        ]);

        $incident->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'status' => $incident->status->value,
        ]);
    }
}
