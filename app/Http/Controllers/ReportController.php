<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('user')->latest()->get();
        return view('admin.reports.indexReports', compact('reports'));
    }

    public function generate(Request $req)
    {
        $req->validate([
            'report_type' => 'required|in:sales,purchases,stock,restock',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $transactions = Transaction::whereBetween('transaction_date', [$req->period_start, $req->period_end])
            ->where('transaction_type', $req->report_type == 'sales' ? 'sales' : 'purchase')
            ->get();

        $summary = [
            'count' => $transactions->count(),
            'total' => $transactions->sum('total'),
            'type' => $req->report_type,
        ];

        Report::create([
            'report_type' => $req->report_type,
            'user_id' => Auth::id(),
            'period_start' => $req->period_start,
            'period_end' => $req->period_end,
            'summary' => $summary,
            'created_at' => now(),
        ]);

        return redirect()->route('reports.index')->with('success', 'Report generated successfully!');
    }

    public function export($id, $format)
    {
        $report = Report::with('user')->findOrFail($id);
        $summary = $report->summary ?? [];

        $filename = "report_{$report->report_type}_{$report->id}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.export', compact('report', 'summary'));
            return $pdf->download("$filename.pdf");
        }

        if ($format === 'csv') {
            $data = new Collection([
                [
                    'Count' => $summary['count'] ?? 0,
                    'Total' => $summary['total'] ?? 0,
                    'Type' => $summary['type'] ?? '-',
                ]
            ]);

            return Excel::download(new class($data) implements FromCollection, WithHeadings {
                private $data;
                public function __construct($data) { $this->data = $data; }
                public function collection() { return $this->data; }
                public function headings(): array { return ['Count', 'Total', 'Type']; }
            }, "$filename.csv");
        }

        abort(404);
    }
}
