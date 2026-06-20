@extends('layouts.app')
@section('content')
    <!-- Hero Section -->
    <section class="min-h-screen flex items-center relative overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900/80 via-gray-800/70 to-purple-900/60"></div>
        </div>

        <div class="container mx-auto px-6 py-20 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <!-- AI Badge -->
                <div
                    class="inline-flex items-center space-x-2 bg-gradient-to-r from-cyan-400/20 to-green-400/20 border border-cyan-400/30 rounded-full px-6 py-2 mt-2 mb-8 animate-pulse-glow">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-sm font-mono font-semibold text-gray-100">AI-POWERED WEBSITE BUILDER</span>
                </div>

                <!-- Main Headline -->
                <div class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight">
                    <div id="typewriter-text" class="typewriter min-h-[1.2em] text-gray-100">
                        ✨ Build Websites with
                    </div>
                    <div class="bg-gradient-to-r from-cyan-400 via-green-400 to-purple-400 bg-clip-text text-transparent">
                        Just a Prompt
                    </div>
                </div>

                <!-- Subtext -->
                <div class="max-w-3xl mx-auto mb-12">
                    <p class="text-xl md:text-2xl text-gray-200 mb-6 leading-relaxed">
                        Every AI can write code.
                        <span class="text-cyan-400 font-semibold">Only AI site-genie creates complete websites.</span>
                    </p>
                    <p class="text-lg text-gray-300 leading-relaxed">
                        Our advanced AI understands your requirements and generates professional, responsive websites in
                        seconds, No coding knowledge required.
                    </p>
                    <p class="text-lg text-gray-300 mt-4 leading-relaxed">
                        That means:
                        <span class="text-green-400">
                            no developers, no templates, no limitations.
                        </span>
                        Just describe what you want and get a ready-to-publish website.
                    </p>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button
                        class="group relative overflow-hidden bg-gradient-to-r from-cyan-400 to-green-400 text-black font-bold text-lg px-8 py-4 rounded-lg hover:scale-105 transition-all duration-300">
                        <span class="relative z-10">🚀 Try It Free Now</span>
                        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                        </div>
                    </button>


                </div>
            </div>
        </div>

        <!-- Floating AI Elements -->
        <div class="absolute top-1/4 left-10 animate-float hidden lg:block">
            <div class="w-20 h-20 bg-cyan-400/20 rounded-full flex items-center justify-center glass-card">
                <svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <div class="absolute top-1/3 right-10 animate-float hidden lg:block" style="animation-delay: 1s;">
            <div class="w-16 h-16 bg-green-400/20 rounded-full flex items-center justify-center glass-card">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z">
                    </path>
                </svg>
            </div>
        </div>
    </section>

    <!-- Builder Section -->
    <section id="builder" class="py-20 relative"
        onclick="document.getElementById('builder').scrollIntoView({behavior: 'smooth'})">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-100">
                    Describe Your Website.
                    <span class="block bg-gradient-to-r from-cyan-400 to-green-400 bg-clip-text text-transparent">
                        Get It Built.
                    </span>
                </h2>
            </div>

            <!-- Prompt Input Section -->
            <div class="max-w-4xl mx-auto">
                <div class="glass-card rounded-2xl p-8 mb-8 hover-tilt">
                    <div class="mb-6">
                        <label for="website-prompt" class="block text-xl font-semibold mb-4 text-gray-100">
                            <i class="fas fa-magic mr-2 text-cyan-400"></i>
                            Describe Your Website
                        </label>
                        <textarea id="website-prompt" rows="6"
                            placeholder="Example: I need a modern portfolio website for a photographer with a dark theme, gallery section, about page, and contact form. It should be elegant and minimalist."
                            class="w-full bg-gray-800/50 border border-gray-600 focus:border-cyan-400 rounded-xl px-4 py-3 text-gray-100 focus:outline-none transition-colors resize-vertical"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button id="generate-btn"
                            class="flex-1 bg-gradient-to-r from-cyan-400 to-green-400 text-black font-bold text-lg py-4 rounded-lg hover:scale-105 transition-all duration-300 flex items-center justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Generate Website
                        </button>


                    </div>
                </div>

                <!-- Preview Section -->
                <div id="preview-wrapper" class="glass-card rounded-2xl p-8 hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-100">
                            <i class="fas fa-eye mr-2 text-cyan-400"></i>
                            Website Preview
                        </h3>
                        <div class="flex space-x-3">
                            <button id="preview-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                                Preview Full Page
                            </button>

                            <button id="edit-btn"
                                class="bg-gray-700 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                                Edit
                            </button>
                            <button id="publish-btn"
                                class="bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold px-4 py-2 rounded-lg hover:scale-105 transition-all">
                                Publish
                            </button>

                            <button id="download-btn"
                                class="bg-gray-700 text-white font-semibold px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                                Download Website
                            </button>

                        </div>

                    </div>

                    <div class="border border-gray-700 rounded-xl overflow-hidden bg-white">
                        <div class="bg-gray-800 py-2 px-4 flex items-center space-x-2">
                            <div class="flex space-x-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="text-gray-300 text-sm flex-1 text-center">Your Generated Website</div>
                        </div>
                        <div id="preview-container" class="relative hidden">
                            <!-- Close button -->
                            <button id="close-preview"
                                class="absolute top-2 right-2 z-50 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600"
                                title="Close preview">
                                ✕
                            </button>

                            {{-- To save an edited website and I want the save button to appear only when the edit is clicked --}}
                            <button id="save-edit-btn" class="hidden bg-blue-500 text-white px-4 py-2 rounded-lg">Save
                                Changes</button>

                            <iframe id="website-preview" class="w-full h-96 border-0 bg-white">
                            </iframe>



                        </div>


                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-100">
                    Not Just Code Generation.
                    <span class="block bg-gradient-to-r from-cyan-400 to-green-400 bg-clip-text text-transparent">
                        Complete Website Creation.
                    </span>
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">Lightning Fast</h3>
                    <p class="text-gray-300">Generate complete websites in under 60 seconds with our advanced AI
                        technology.</p>
                </div>

                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">Fully Responsive</h3>
                    <p class="text-gray-300">Every website is automatically optimized for all devices - desktop,
                        tablet,
                        and mobile.</p>
                </div>

                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">SEO Optimized</h3>
                    <p class="text-gray-300">Built-in SEO best practices to help your website rank higher in search
                        results.</p>
                </div>

                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">Custom Designs</h3>
                    <p class="text-gray-300">Unique designs generated for each project, not just template variations.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">Secure Hosting</h3>
                    <p class="text-gray-300">One-click publishing with secure, fast hosting included with every
                        website.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 hover-tilt">
                    <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-gray-100">Continuous Updates</h3>
                    <p class="text-gray-300">Your website stays current with the latest web standards and technologies.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 relative">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-100">
                    Ready to Build Your Website
                    <span class="block bg-gradient-to-r from-cyan-400 to-green-400 bg-clip-text text-transparent">
                        in 60 Seconds?
                    </span>
                </h2>
                <p class="text-xl text-gray-200 mb-8 max-w-3xl mx-auto">
                    Join thousands of businesses, creators, and entrepreneurs who have launched their websites with AI
                    site-genie.
                    No coding, no templates, no limitations.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button
                        class="group relative overflow-hidden bg-gradient-to-r from-cyan-400 to-green-400 text-black font-bold text-lg px-8 py-4 rounded-lg hover:scale-105 transition-all duration-300">
                        <span class="relative z-10">🚀 Start Building Free</span>
                        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                        </div>
                    </button>

                </div>

                <p class="text-gray-400 mt-6 text-sm">
                    No credit card required. Free plan includes 3 website generations.
                </p>
            </div>
        </div>
    </section>


    <!-- Chatbot -->
    <div id="chatbot" class="fixed bottom-6 right-6 z-50">
        <button id="chatbot-toggle"
            class="w-16 h-16 bg-gradient-to-r from-cyan-400 to-green-400 rounded-full text-black font-bold text-2xl hover:scale-110 transition-all duration-300 animate-pulse-glow relative overflow-hidden">
            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                </path>
            </svg>
        </button>

        <div id="chatbot-panel"
            class="absolute bottom-20 right-0 w-80 h-96 glass-card rounded-2xl p-4 shadow-2xl border border-white/10 hidden">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg text-gray-100">AI site-genie Assistant</h3>
                <button id="chatbot-close" class="text-gray-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div id="chatbot-messages" class="h-64 overflow-y-auto space-y-2 mb-4 text-sm text-gray-200">
                <!-- Chat messages will appear here -->
            </div>


            <div class="flex space-x-2">
                <input id="chatbot-input" type="text" placeholder="Ask about website generation..."
                    class="flex-1 bg-gray-800/50 border border-gray-600 text-sm focus:border-cyan-400 rounded-lg px-3 py-2 text-gray-100 focus:outline-none" />
                <button id="chatbot-send"
                    class="bg-gradient-to-r from-cyan-400 to-green-400 text-black font-semibold text-sm px-4 py-2 rounded-lg">
                    Send
                </button>
            </div>
        </div>
    </div>

    <script>
        window.currentProjectSlug = "{{ $project->slug ?? '' }}";
    </script>
@endsection;
