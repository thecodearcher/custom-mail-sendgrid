<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use SendGrid;

class MailingController extends Controller
{

    public function index()
    {
        return view('welcome', ['users' => User::all()]);

    }

    public function sendMail(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|email',
            'users' => 'required|array',
            'users.*' => 'required',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $from = new SendGrid\Mail\From($validated['from']);

        /* Add selected users email to $tos array */
        $tos = [];
        foreach ($validated['users'] as $user) {
            array_push($tos, new SendGrid\Mail\To(json_decode($user)->email, json_decode($user)->name));
        }

        /* Sent subject of mail */
        $subject = new SendGrid\Mail\Subject($validated['subject']);

        /* Set mail body */
        $htmlContent = new SendGrid\Mail\HtmlContent($validated['body']);

        $email = new SendGrid\Mail\Mail(
            $from,
            $tos,
            $subject,
            null,
            $htmlContent
        );

        /* Create instance of Sendgrid SDK */
        $sendgrid = new SendGrid(getenv('SENDGRID_API_KEY'));

        /* Send mail using sendgrid instance */
        $response = $sendgrid->send($email);
        if ($response->statusCode() == 202) {
            return redirect()->route('welcome')->with(['success' => "E-mails successfully sent out!!"]);
        }

        return back()->withErrors(json_decode($response->body())->errors);
    }
}
