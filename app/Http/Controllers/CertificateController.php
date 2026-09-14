<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    /** Public verification: anyone with the serial can confirm it is real. */
    public function verify(string $serial): Response
    {
        $certificate = Certificate::with(['user:id,name', 'course:id,title'])
            ->where('serial', $serial)
            ->first();

        return Inertia::render('certificates/verify', [
            'serial' => $serial,
            'certificate' => $certificate ? [
                'serial' => $certificate->serial,
                'issued_at' => $certificate->issued_at,
                'holder' => $certificate->user->name,
                'course' => $certificate->course->title,
            ] : null,
        ]);
    }

    public function download(Request $request, Certificate $certificate): HttpResponse
    {
        abort_unless(
            $certificate->user_id === $request->user()->id || $request->user()->isAdmin(),
            403,
        );

        $certificate->load(['user:id,name', 'course:id,title']);

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'verifyUrl' => route('certificates.verify', $certificate->serial),
        ])
            ->setPaper('a4', 'landscape')
            ->download("certificate-{$certificate->serial}.pdf");
    }
}
