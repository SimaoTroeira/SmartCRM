<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyInvite;
use App\Models\User;
use App\Models\UserCompanyRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Mail\CompanyInvitationMail;
use Illuminate\Support\Facades\Mail;

class CompanyInviteController extends Controller
{
    public function invite(Request $request, $companyId)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Email inválido ou utilizador não registado.'], 422);
        }

        $userEmail = $request->email;
        $token = Str::random(40);

        $invite = CompanyInvite::create([
            'company_id' => $companyId,
            'email' => $userEmail,
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $link = 'http://localhost:5173' . '/accept-invite/' . $token;
        // $link = env('FRONTEND_URL') . '/accept-invite/' . $token;
        // $link = config('services.frontend.url') . '/accept-invite/' . $token;


        Mail::to($userEmail)->send(new CompanyInvitationMail($link, Company::find($companyId)->name));

        return response()->json([
            'message' => 'Convite enviado com sucesso!',
            'token' => $token,
        ]);
    }

    public function accept($token)
    {
        $invite = CompanyInvite::where('token', $token)->first();

        if (!$invite || $invite->isExpired() || $invite->isAccepted()) {
            return response()->json(['error' => 'Convite inválido ou expirado.'], 400);
        }

        $user = auth()->user();
        if (!$user || $user->email !== $invite->email) {
            return response()->json(['error' => 'Não autorizado.'], 403);
        }

        UserCompanyRole::create([
            'user_id' => $user->id,
            'company_id' => $invite->company_id,
            'role_id' => 3, // ID fixo para 'CU' (Company User)
        ]);

        $invite->accepted_at = now();
        $invite->save();

        return response()->json(['message' => 'Convite aceite com sucesso.']);
    }

    public function resend($id)
    {
        $invite = CompanyInvite::findOrFail($id);

        if ($invite->isCancelled()) {
            return response()->json(['error' => 'Este convite foi cancelado.'], 400);
        }

        $invite->token = Str::random(40);
        $invite->expires_at = now()->addMinutes(10);
        $invite->resended_at = now();
        $invite->save();

        $link = env('FRONTEND_URL') . '/accept-invite/' . $invite->token;

        Mail::to($invite->email)->send(new CompanyInvitationMail($link, $invite->company->name));

        return response()->json(['message' => 'Convite reenviado com sucesso.', 'token' => $invite->token]);
    }

    public function cancel($id)
    {
        $invite = CompanyInvite::findOrFail($id);
        $invite->cancelled_at = now();
        $invite->save();

        return response()->json(['message' => 'Convite cancelado com sucesso.']);
    }
}
