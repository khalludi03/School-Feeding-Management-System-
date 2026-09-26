<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolParticipationPeriod;
use App\Services\Form12Service;
use App\Services\Form4Service;
use App\Services\ZipReportWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportGeneratorController extends Controller
{
    private const FORM_OPTIONS = [
        '4' => ['label' => 'Form 4 – School Receipt Register', 'desc' => 'Per-school daily receipt details for a period', 'per_school' => true],
        '7' => ['label' => 'Form 7 – Chalan Totals', 'desc' => 'School-wise chalan counts and quantities', 'per_school' => false],
        '10' => ['label' => 'Form 10 – Invoice', 'desc' => 'Invoice from actual receipts for the upazila', 'per_school' => false],
        '12' => ['label' => 'Form 12 – School Stock Statement', 'desc' => 'Per-school opening balance, receipts, distribution, closing', 'per_school' => true],
        '13' => ['label' => 'Form 13 – Upazila Consolidated Stock', 'desc' => 'All-school consolidated stock statement', 'per_school' => false],
    ];

    public function index(): View
    {
        $participatingSchools = School::query()
            ->where('is_active', true)
            ->whereHas('participationPeriods', fn ($q) => $q->whereDate('starts_on', '<=', Carbon::today()))
            ->orderBy('bangla_name')
            ->pluck('bangla_name', 'id');

        return view('admin.reports.choose', [
            'formOptions' => self::FORM_OPTIONS,
            'participatingSchools' => $participatingSchools,
        ]);
    }

    public function generate(Request $request, ZipReportWriter $zipWriter): HttpResponse
    {
        $validator = Validator::make($request->all(), [
            'form_type' => 'required|in:4,7,10,12,13',
            'period_mode' => 'required|in:month,range',
            'month' => 'required_if:period_mode,month|nullable',
            'start_date' => 'required_if:period_mode,range|nullable',
            'end_date' => 'required_if:period_mode,range|nullable',
            'school_id' => 'nullable|exists:schools,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $formType = $request->input('form_type');
        $periodMode = $request->input('period_mode');
        $schoolId = $request->input('school_id');
        $errors = [];

        if ($periodMode === 'range') {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));

            if ($endDate->lt($startDate)) {
                return redirect()->back()->withErrors(['end_date' => 'End date must be on or after start date.'])->withInput();
            }
        }

        if ($periodMode === 'month') {
            $month = $request->input('month');
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                return redirect()->back()->withErrors(['month' => 'Invalid month format.'])->withInput();
            }
            [$year, $m] = explode('-', $month);
            $periodParams = '?month='.$month;
        } else {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $periodParams = '?start='.$startDate->toDateString().'&end='.$endDate->toDateString();
        }

        $perSchool = self::FORM_OPTIONS[$formType]['per_school'];

        if ($perSchool && ! $schoolId) {
            return $this->generateZipBundle($formType, $periodMode, $request, $zipWriter);
        }

        return $this->redirectToReport($formType, $periodParams, $schoolId);
    }

    private function redirectToReport(string $formType, string $periodParams, ?int $schoolId)
    {
        $url = match ($formType) {
            '4' => $schoolId ? route('admin.form4.pdf', ['school' => $schoolId]).$periodParams : null,
            '7' => route('admin.form7.pdf').$periodParams,
            '10' => route('admin.form10.pdf').$periodParams,
            '12' => $schoolId ? route('admin.form12.pdf', ['school' => $schoolId]).$periodParams : null,
            '13' => route('admin.form13.pdf').$periodParams,
            default => null,
        };

        if (! $url) {
            return redirect()->back()->withErrors(['school_id' => 'School is required for Form 4 and Form 12.']);
        }

        return redirect($url);
    }

    private function generateZipBundle(string $formType, string $periodMode, Request $request, ZipReportWriter $zipWriter)
    {
        if ($periodMode === 'range') {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $period = 'form'.$formType.'-'.$startDate->format('Ymd').'-to-'.$endDate->format('Ymd');
        } else {
            $month = $request->input('month');
            [$year, $m] = explode('-', $month);
            $period = 'form'.$formType.'-'.$year.'-'.$m;
        }

        $participatingSchoolIds = SchoolParticipationPeriod::query()
            ->whereDate('starts_on', '<=', Carbon::today())
            ->pluck('school_id')
            ->unique();

        $schools = School::query()
            ->whereIn('id', $participatingSchoolIds)
            ->where('is_active', true)
            ->orderBy('bangla_name')
            ->get();

        $pdfs = [];

        foreach ($schools as $school) {
            $response = null;

            if ($formType === '4') {
                if ($periodMode === 'range') {
                    $response = app(Form4Controller::class)->pdf(
                        new Request(['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()]),
                        $school,
                        app(Form4Service::class),
                        app(Mpdf::class)
                    );
                } else {
                    $response = app(Form4Controller::class)->pdf(
                        new Request(['month' => $request->input('month')]),
                        $school,
                        app(Form4Service::class),
                        app(Mpdf::class)
                    );
                }
            } elseif ($formType === '12') {
                if ($periodMode === 'range') {
                    $response = app(Form12Controller::class)->pdf(
                        new Request(['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()]),
                        $school,
                        app(Form12Service::class),
                        app(Mpdf::class)
                    );
                } else {
                    $response = app(Form12Controller::class)->pdf(
                        new Request(['month' => $request->input('month')]),
                        $school,
                        app(Form12Service::class),
                        app(Mpdf::class)
                    );
                }
            }

            if (isset($response) && $response->getStatusCode() === 200) {
                $filename = 'form'.$formType.'-'.$school->code.'-'.$period.'.pdf';
                $pdfs[$filename] = $response->getContent();
            }
        }

        if (empty($pdfs)) {
            return redirect()->back()->withErrors(['school_id' => 'No school data found for the selected period.']);
        }

        $zipFilename = $period.'-bundle.zip';
        $zipContent = $zipWriter->create('Form'.$formType.'-Reports', $pdfs);

        return response($zipContent, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$zipFilename.'"',
            'Content-Length' => (string) strlen($zipContent),
        ]);
    }
}
