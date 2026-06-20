<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateImageJob;
use App\Models\ImageJob;
use App\Models\Project;
use App\Models\ProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;


class SiteGeneratorController extends Controller
{

    // POST /generate
    public function generate(Request $request)
    {
        set_time_limit(300); // 5 minutes, adjust as needed
        try {
            $request->validate(['prompt' => 'required|string']);
            $prompt = $request->input('prompt');

            $system = <<<SYS
                You are an elite AI website designer and front-end developer specializing in modern, web design.

                Your task:
                Generate ONE complete, breathtaking HTML website that looks both elegant, modern, and visually stunning. The design should rival top-tier websites like Stripe, Linear, Vercel, or Apple. Return only raw HTML.

                ==============================
                MANDATORY HEADER / NAVBAR
                ==============================
                - Responsive navbar at the top
                - Sleek, glass-morphism or blurred background
                - Navbar MUST include:
                - Futuristic brand logo/text with gradient
                - Navigation links: Home, About, Services, Portfolio, Blog
                - Desktop: horizontal menu with subtle hover effects
                - Mobile: animated hamburger menu with smooth transitions
                - Add subtle glow or shadow effects on scroll
                - Use Tailwind CSS utility classes only

                ==============================
                MANDATORY SITE SECTIONS (NO CONTACT FORM)
                ==============================

                1. HERO / HOME
                - Bold headline with gradient text
                - Animated or dynamic subheadline
                - Two CTA buttons with hover effects
                - Abstract 3D-like hero image or illustration
                - Optional: floating elements, particles, or subtle animations

                2. ABOUT / STORY
                - Detailed company/project description (mission, vision, story)
                - Cards, images, or icons with modern layout

                3. SERVICES / PRODUCTS / PORTFOLIO
                - Card/grid layout
                - Multiple items with titles, descriptions, and icons
                - Hover effects (scale, glow, shadow)
                - Asymmetric or overlapping design

                4. FEATURES / HIGHLIGHTS
                - Showcase key features/benefits
                - Include icons, small illustrations, or micro-interactions
                - Modern layouts (zig-zag, alternating cards, split sections)

                5. METRICS / STATISTICS
                - Highlight key numbers or stats
                - Large, bold typography
                - Animated counters or hover micro-interactions

                6. TESTIMONIALS / SOCIAL PROOF
                - Carousel or grid layout
                - Avatars, names, quotes
                - Optional company logos strip

                7. BLOG / NEWS (optional)
                - Cards for latest posts
                - Title, snippet, read-more links
                - Modern hover effects

                8. PRICING / PLANS (if relevant)
                - Toggle monthly/yearly
                - Highlight recommended plan
                - Minimal, futuristic cards with hover effects

                9. FAQ / HELPFUL INFO
                - Accordion-style questions
                - Smooth open/close animations
                - Clean typography

                10. CTA / PROMO
                - Bold statement with gradient background
                - Single primary button
                - Minimal design with maximum impact

                ==============================
                MANDATORY FOOTER
                ==============================
                - Multi-column futuristic footer
                - MUST contain:
                - Logo with tagline
                - Navigation links (Home, About, Services, Portfolio, Blog)
                - Social media icons with hover effects
                - Newsletter design section (no form, just design)
                - Copyright with dynamic year
                - Visual elements: gradient dividers, subtle patterns, hover effects
                - Fully responsive with clever stacking on mobile

                ==============================
                DESIGN & STYLE RULES
                ==============================
                - Futuristic and modern aesthetic
                - Use a color that fits and better defines the prompt request (e.g blue for school, green for environment or agriculture, neon for tech, etc)
                - Smooth transitions (all animations ~300ms)
                - Bold, readable typography with contrast
                - Asymmetric layouts allowed
                - Fully responsive (mobile-first)
                - Tailwind CSS via CDN
                - Strictly no contact form
                - Subtle scroll animations encouraged

                ==============================
                IMAGE RULES
                ==============================
                - Every image MUST use EXACTLY this format:

                <img
                src="IMAGE_PLACEHOLDER_{TOKEN}"
                data-image="{TOKEN}"
                alt="{DESCRIPTION}"
                class="[tailwind classes]"
                >

                - Include ONE IMAGES JSON comment at the END:

                <!-- IMAGES
                {
                "images": [
                    {
                    "token": "TOKEN",
                    "prompt": "Detailed futuristic image prompt with style keywords: 3D render, octane render, futuristic, neon lighting, cinematic, 8k, high quality",
                    "size": "1024x1024"
                    }
                ]
                }
                -->

                - Tokens MUST match between img tags and JSON block
                - Use 4-6 images maximum for key sections
                - Return ONLY raw HTML, no explanations
                SYS;

            $userPrompt = <<<PROMPT
                            Design a futuristic, cutting-edge website for:

                            {$prompt}

                            Requirements:
                            - No contact form (replace with newsletter design section if needed)
                            - Must have at least 6 distinct, rich sections
                            - Footer must be comprehensive and beautifully designed
                            - Dark theme with neon/electric accents
                            - Modern hover effects, animations, and scroll interactions
                            - Look better than any existing website in this niche
                            - Design should feel innovative, ahead of its time, and fully responsive
                            PROMPT;



            $llmResp = Http::timeout(120)->withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => env('APP_URL'),
                'X-Title'       => 'site-genie AI Website Builder'
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openai/gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                // 'max_tokens' => 1500,
            ]);

            if (!$llmResp->successful()) {
                Log::error('LLM failed', ['body' => $llmResp->body()]);
                return response()->json(['error' => 'LLM failed'], 500);
            }

            $responseJson = $llmResp->json();

            $html = $responseJson['choices'][0]['message']['content'] ?? null;

            if (!$html || !is_string($html)) {
                Log::error('Invalid LLM response', $responseJson);
                return response()->json(['error' => 'Invalid AI response'], 500);
            }

            // Remove markdown opening ```html
            $html = preg_replace('/^```html\s*/i', '', $html);

            // Remove markdown closing ```
            $html = preg_replace('/```$/', '', $html);
            $raw_html_response = $html;

            // Decode escaped HTML characters (like \u003C)
            $html = html_entity_decode($html);

            // Trim extra whitespace
            $html = trim($html);

            $slug = Str::slug($prompt) . '-' . Str::random(5);

            // =============================
            // Extract IMAGES JSON FIRST
            // =============================
            $imagesMeta = [];

if (preg_match('/<!--\s*IMAGES\s*(\{.*?\})\s*-->/s', $html, $match)) {
                $decoded = json_decode($match[1], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $imagesMeta = $decoded['images'] ?? [];
                }
            }

            // =============================
            // Inject Unsplash fallback
            // =============================
            $html = $this->injectUnsplashFallback($html, $imagesMeta);

            // =============================
            // Create Project
            // =============================
            $project = Project::create([
                'title'    => Str::limit($prompt, 80),
                'prompt'   => $prompt,
                'slug'     => $slug,
                'raw_html' => $raw_html_response,
                'html'     => $html,
                'status'   => 'generating',
                'images'   => [],
            ]);




            $storedImages = [];

            foreach ($imagesMeta as $meta) {
                $token  = strtolower($meta['token'] ?? 'img_' . Str::random(6));
                $imgPrompt = $meta['prompt'] ?? $project->prompt;
                $size   = $meta['size'] ?? '1024x1024';

                $job = ImageJob::create([
                    'project_id' => $project->id,
                    'token'      => $token,
                    'prompt'     => $imgPrompt,
                    'size'       => $size,
                    'status'     => 'queued',
                ]);

                GenerateImageJob::dispatch($job->id);

                $storedImages[] = [
                    'token'  => $token,
                    'prompt' => $imgPrompt,
                    'size'  => $size,
                    'status' => 'queued',
                    'url'   => null,
                ];
            }

            $project->update(['images' => $storedImages]);

            // save initial system message
            ProjectMessage::create([
                'project_id' => $project->id,
                'role'       => 'system',
                'content'    => 'Initial website generated',
            ]);

            ProjectMessage::create([
                'project_id' => $project->id,
                'role'       => 'ai',
                'content'    => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Generate failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }




        return response()->json([
            'project_id' => $project->id,
            'slug'       => $project->slug,
            'html'       => $project->html,
            'status'     => $project->status,
            'images'     => $project->images,
            'success'    => true,
            'redirect_url' => "/preview/{$project->slug}",
        ]);
    }

    // Inject Unsplash images
    private function injectUnsplashFallback(string $html, array $imagesMeta): string
    {
        foreach ($imagesMeta as $meta) {
            $token = strtolower($meta['token'] ?? '');
            $prompt = $meta['prompt'] ?? 'modern website';
            $size = $meta['size'] ?? '1200x800';

            if (!$token) continue;

            $unsplash = "https://source.unsplash.com/{$size}/?" . urlencode($prompt);

            $html = preg_replace_callback(
                '/<img\b[^>]*data-image=["\']' . preg_quote($token, '/') . '["\'][^>]*>/i',
                function ($matches) use ($unsplash) {
                    return preg_replace(
                        '/src=["\'][^"\']*["\']/i',
                        'src="' . $unsplash . '"',
                        $matches[0]
                    );
                },
                $html
            );
        }

        return $html;
    }


    // Preview
    public function previewBySlug($slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        return view('preview', compact('project'));
    }



    // This will be removed later if after checking and there is need to remove it
    // Chat Edit
    public function chatEdit(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        $project = Project::findOrFail($id);

        // Save user message
        ProjectMessage::create([
            'project_id' => $id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // Build conversation
        $messages = [
            [
                'role' => 'system',
                'content' => <<<SYS
                            You are an AI website editor.
                            - You receive FULL HTML
                            - Apply requested changes
                            - Return FULL UPDATED HTML ONLY
                            - KEEP image placeholders
                            SYS
            ],
            [
                'role' => 'assistant',
                'content' => $project->raw_html,
            ],
            [
                'role' => 'user',
                'content' => $request->message,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'openai/gpt-4o-mini',
            'messages' => $messages,
            // 'max_tokens' => 1500,
        ]);

        $html = data_get($response->json(), 'choices.0.message.content');
        if (!$html) {
            return response()->json(['error' => 'AI edit failed'], 500);
        }



        $project->update([
            'raw_html' => $html,
            'html' => $html,
            'status' => 'editing',
        ]);

        // Save AI summary 
        ProjectMessage::create([
            'project_id' => $id,
            'role' => 'ai',
            'content' => 'Website updated successfully.',
        ]);

        return response()->json([
            'html' => $html,
        ]);
    }


    // Update HTML
    public function updateHtml(Request $request, $id)
    {
        $request->validate([
            'html' => 'required|string'
        ]);

        $project = Project::findOrFail($id);

        $project->update([
            'raw_html' => $request->html,
            'html' => $request->html,
            'status' => 'editing'
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    // Upload image when editing 
    public function uploadImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|max:2048' // 2MB max
    ]);

    $path = $request->file('image')->store('uploads', 'public');

    return response()->json([
        'url' => asset("storage/$path")
    ]);
}


    // Publish
    public function publish($id)
    {
        $project = Project::findOrFail($id);

        $project->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        $filePath = "published/{$project->id}.html";

        \Illuminate\Support\Facades\Storage::disk('public')
            ->put($filePath, $project->html);

        return response()->json([
            'success' => true,
            'url' => asset("storage/{$filePath}")
        ]);
    }



    public function renderProject($slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        return response($project->html)
            ->header('Content-Type', 'text/html');
    }


    // Served Published
    public function servePublished($id)
    {
        $project = Project::findOrFail($id);

        if ($project->status !== 'published') {
            abort(404, 'Website not published yet.');
        }

        $html = $project->html;

        //  Remove edit mode
        $html = str_replace('contenteditable="true"', '', $html);
        $html = str_replace('contenteditable=true', '', $html);

        return response($html)
            ->header('Content-Type', 'text/html');
    }


    // Download
    public function download($id)
    {
        $project = Project::findOrFail($id);

        $zipName = "project_{$project->id}.zip";
        $zipPath = storage_path("app/public/temp/{$zipName}");

        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($zipPath));

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Cannot create zip file');
        }

        // Add HTML
        $zip->addFromString('index.html', $project->html);

        // Add images safely
        $images = $project->images ?? [];

        foreach ($images as $img) {
            if (!empty($img['url']) && !empty($img['token'])) {

                $path = parse_url($img['url'], PHP_URL_PATH);
                $fullPath = public_path($path);

                if (file_exists($fullPath)) {
                    $contents = file_get_contents($fullPath);
                    $zip->addFromString("images/{$img['token']}.png", $contents);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }




    // Status
    public function status($id)
    {
        $jobs = ImageJob::where('project_id', $id)->get();

        return response()->json([
            'completed' => $jobs->where('status', 'done')->count(),
            'total'     => $jobs->count(),
            'jobs'      => $jobs,
            'progress'  => $jobs->count()
                ? round(($jobs->where('status', 'done')->count() / $jobs->count()) * 100)
                : 0
        ]);
    }


    // HTML
    public function getHtml($id)
    {
        $project = Project::findOrFail($id);

        return response()->json([
            'html'   => $project->html,
            'status' => $project->status,
        ]);
    }


    private function normalizeImagePlaceholders(string $html): string
    {
        // Replace IMAGE_PLACEHOLDER_* tokens
        $html = preg_replace_callback(
            '/<img[^>]+src=["\']IMAGE_PLACEHOLDER_([^"\']+)["\'][^>]*>/i',
            function ($matches) {

                $token = strtolower(trim($matches[1]));
                $size = "1200x600";

                $imgPrompt = str_replace('-', ' ', $token);

                $unsplashUrl = "https://source.unsplash.com/{$size}/?" . urlencode($imgPrompt);

                return <<<HTML
<img
    src="{$unsplashUrl}"
    data-image="{$token}"
    class="ai-image w-full h-auto rounded"
    alt="{$imgPrompt}"
>
HTML;
            },
            $html
        );

        // Replace via.placeholder.com images returned by AI
        $html = preg_replace_callback(
            '/<img[^>]+src=["\']https?:\/\/via\.placeholder\.com\/[^"\']+["\'][^>]*>/i',
            function () {

                $token = 'img_' . strtolower(\Illuminate\Support\Str::random(6));
                $unsplashUrl = "https://source.unsplash.com/1200x600/?business";

                return <<<HTML
<img
    src="{$unsplashUrl}"
    data-image="{$token}"
    class="ai-image w-full h-auto rounded"
    alt="{$token}"
>
HTML;
            },
            $html
        );

        return $html;
    }
}
