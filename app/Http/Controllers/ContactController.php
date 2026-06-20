<?php

namespace App\Http\Controllers;

use App\Mail\ContactAdminMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function contact()
    {
        return view('pages.contact');
    }

    public function submitContactForm(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        try {

            // ✔ Send to YOUR admin email
            Mail::to('uvincent54@gmail.com')->queue(new ContactAdminMail($validated));

            return back()->with('success', 'Your message has been sent!');
        } catch (\Exception $e) {
            Log::error('Contact form submission failed: ' . $e->getMessage());

            return back()->with('error', 'Oops! Something went wrong. Please try again later.');
        }
    }
}
