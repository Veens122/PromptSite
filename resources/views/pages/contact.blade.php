@extends('layouts.app')
@section('content')
    <div class="contact">
        <h1 class="text-center text-2xl font-bold mb-6">CONTACT US</h1>

        <div class="flex flex-col lg:flex-row gap-10 items-center px-6 w-full">

            <!-- LEFT: Contact Info -->


            <!-- RIGHT: Contact Form -->
            <div class="w-full lg:w-2/3 container mx-auto  py-6">
                <p class="mb-4">If you have any questions, feel free to reach out to us!</p>

                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium ">Name</label>
                        <input type="text" name="name" id="name" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 
           focus:ring-cyan-500 focus:border-cyan-500
           bg-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" id="email" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 
           focus:ring-cyan-500 focus:border-cyan-500
           bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium ">Subject</label>
                        <input type="text" name="subject" id="subject" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 
           focus:ring-cyan-500 focus:border-cyan-500
           bg-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Message</label>
                        <textarea name="message" id="message" rows="4" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 
           focus:ring-cyan-500 focus:border-cyan-500
           bg-gray-900"></textarea>
                    </div>

                    <button type="submit" class="bg-cyan-500 text-white px-4 py-2 rounded-md  hover:bg-cyan-600">
                        Send Message
                    </button>
                </form>
            </div>

            <div class="w-full lg:w-1/3 ">
                <p>You can reach us via these numbers and email</p>
                <ul class="">
                    <li class="py-2">Phone: +234813841762</li>
                    <li>Email: site-genie@gmail.com</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
