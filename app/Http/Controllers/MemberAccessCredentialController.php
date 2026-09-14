<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\Enums\AccessCredentialType;
use App\Models\Member;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Support\Header;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberAccessCredentialController extends Controller
{
    public function __construct(
        private readonly MemberAccessCredentialServiceInterface $memberAccessCredentialService
    ) {}

    public function issueQr(Request $request, Member $member): RedirectResponse
    {
        $issued = $this->memberAccessCredentialService->issue(
            $member,
            AccessCredentialType::QR,
            'Membership QR'
        );

        $request->session()->put(
            $this->sessionKey($member),
            Crypt::encryptString($issued->token)
        );

        return redirect()->route('members.qr.show', $member);
    }

    public function registerRfid(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:512'],
        ]);

        $this->memberAccessCredentialService->issueRfidCredential(
            $member,
            $validated['credential']
        );

        return back()->with('success', 'RFID card registered successfully.');
    }

    public function showQr(Request $request, Member $member): Response|RedirectResponse
    {
        if (
            $request->header(Header::INERTIA)
            && $request->header(Header::VERSION, '') !== Inertia::getVersion()
        ) {
            return Inertia::render('Members/QrCredential');
        }

        $encryptedToken = $request->session()->pull($this->sessionKey($member));

        if (! $encryptedToken) {
            return redirect()
                ->route('members.show', $member)
                ->with('error', 'Issue a new QR credential to view its card.');
        }

        try {
            $token = Crypt::decryptString($encryptedToken);
        } catch (DecryptException) {
            return redirect()
                ->route('members.show', $member)
                ->with('error', 'The issued QR credential could not be read.');
        }

        Inertia::encryptHistory();

        return Inertia::render('Members/QrCredential', [
            'member' => $member->only([
                'id',
                'membership_number',
                'first_name',
                'last_name',
            ]),
            'qrCode' => (string) QrCode::format('svg')
                ->size(320)
                ->margin(1)
                ->generate($token),
        ]);
    }

    private function sessionKey(Member $member): string
    {
        return "issued_qr_credentials.{$member->id}";
    }
}
