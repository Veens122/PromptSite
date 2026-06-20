<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateImageJob;
use App\Models\ImageJob;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteGeneratorController extends Controller
{
    /**
     * Entrypoint to generate a site HTML and enqueue image jobs.
     */
    public function generate(Request $request)
    {
        try {
            $prompt = $request->input('prompt', '');
            
            // Validate prompt
            if (!$prompt || empty(trim($prompt))) {
                return response()->json([
                    'error' => 'Prompt is required'
                ], 400);
            }

            // Call LLM to generate HTML
            $html = $this->generateHtmlFromLLM($prompt);

            if (!$html) {
                // Fallback to stub if LLM fails
                $html = $this->stubHtmlForPrompt($prompt);
            }

            // Check if HTML appears truncated; if so, request continuation
            if ($this->isHtmlTruncated($html)) {
                $cont = $this->requestHtmlContinuation($html);
                if ($cont) {
                    $html .= $cont;
                }
            }

            // Normalize placeholders and extract images metadata
            $html = $this->normalizeImagePlaceholders($html);
            $images = $this->extractImagesMetadataFromHtml($html);

            // If no metadata extracted, try secondary LLM-based extraction
            if (empty($images)) {
                Log::info('No IMAGES metadata found, requesting secondary extraction');
                $images = $this->requestImagesMetaFromHtml($html);
            }

            // Postprocess: append hidden placeholders for any missing tokens, footer, FAQ
            $html = $this->postprocessHtml($html, $images);

            // Create project record
            $project = Project::create([
                'title' => Str::limit($prompt, 120),
                'html' => $html,
                'raw_html' => $html,
            ]);

            // Create ImageJob rows and dispatch jobs
            foreach ($images as $img) {
                $job = ImageJob::create([
                    'project_id' => $project->id,
                    'token' => $img['token'] ?? Str::random(8),
                    'prompt' => $img['prompt'] ?? ($img['token'] ?? 'image'),
                    'size' => $img['size'] ?? '1024x1024',
                    'status' => 'pending',
                ]);

                GenerateImageJob::dispatch($job->id)->onQueue('default');
            }

            return response()->json([
                'project_id' => $project->id,
                'html' => $project->html,
                'images_created' => count($images)
            ], 200);
            
        } catch (\Throwable $e) {
            Log::error('Generate error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Failed to generate site: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call OpenRouter LLM to generate HTML from a prompt.
     */
    private function generateHtmlFromLLM(string $prompt): ?string
    {
        $apiKey = env('OPENROUTER_API_KEY');
        if (!$apiKey) {
            Log::warning('OpenRouter API key not configured');
            return null;
        }

        $systemPrompt = "You are an expert HTML/CSS web designer. Generate a complete, production-ready HTML mockup for a website based on the user's prompt. " .
                       "Include realistic content, modern styling with Tailwind CSS or inline styles, and placeholder images using <!-- IMAGES {...} --> comment blocks. " .
                       "For images, use IMAGE_PLACEHOLDER_{TOKEN} as src and include a comment like: <!-- IMAGES { \"images\": [{\"token\": \"hero\", \"prompt\": \"description\", \"size\": \"1024x1024\"}] } --> " .
                       "Return ONLY the HTML, no markdown, no explanations. Start with <!doctype html>.";

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                ])
                ->post(config('services.openrouter.base_url'), [
                    'model' => 'openai/gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Create a website for: ' . $prompt,
                        ],
                    ],
                    'max_tokens' => 4000,
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                Log::warning('OpenRouter LLM request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $body = $response->json();
            $html = $body['choices'][0]['message']['content'] ?? '';
            
            if (!$html) {
                Log::warning('OpenRouter returned empty content');
                return null;
            }

            // Clean up markdown code fences if present
            $html = preg_replace('/^```html\s*/i', '', $html);
            $html = preg_replace('/```\s*$/i', '', $html);
            $html = trim($html);

            Log::info('HTML generated successfully from LLM', ['length' => strlen($html)]);
            return $html;

        } catch (\Exception $e) {
            Log::error('LLM generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Replace various placeholder permutations into a canonical form
     * so downstream consumers can reliably scan for tokens.
     */
    private function normalizeImagePlaceholders(string $html): string
    {
        if (! $html) return $html;

        // Common variants -> canonical: IMAGE_PLACEHOLDER_{TOKEN}
        // e.g. src="IMAGE_PLACEHOLDER_hero" or src="/images/IMAGE_PLACEHOLDER_hero.png"
        $patterns = [
            '/src=["\']?IMAGE_PLACEHOLDER_([a-zA-Z0-9_-]+)["\']?/i',
            '/src=["\']?\/?storage\/projects\/[0-9]+\/images\/([a-zA-Z0-9_-]+)\.(png|jpg|webp)["\']?/i',
            '/data-image=["\']?([a-zA-Z0-9_-]+)["\']?/i',
        ];

        // No attempt to rewrite actual URLs; just ensure img tags have data-image and canonical src token
        $html = preg_replace_callback('/<img[^>]*>/i', function ($m) {
            $tag = $m[0];
            // find token from data-image
            if (preg_match('/data-image=["\']([a-zA-Z0-9_-]+)["\']/i', $tag, $d)) {
                $token = $d[1];
            } elseif (preg_match('/IMAGE_PLACEHOLDER_([a-zA-Z0-9_-]+)/i', $tag, $t)) {
                $token = $t[1];
            } else {
                // try filename
                if (preg_match('/src=["\'][^"\']*\/([a-zA-Z0-9_-]+)\.(png|jpg|webp)["\']/i', $tag, $f)) {
                    $token = $f[1];
                } else {
                    return $tag;
                }
            }

            // ensure data-image exists
            if (!preg_match('/data-image=/i', $tag)) {
                $tag = preg_replace('/<img/i', '<img data-image="'.$token.'"', $tag, 1);
            }

            // ensure src uses canonical placeholder if it currently points to a placeholder-like token
            if (!preg_match('/src=["\']\/storage\//i', $tag)) {
                // if src already points to an absolute/http url, keep it
                if (!preg_match('/src=["\']https?:\/\//i', $tag)) {
                    if (preg_match('/src=["\']?IMAGE_PLACEHOLDER_([a-zA-Z0-9_-]+)["\']?/i', $tag)) {
                        // already canonical
                    } else {
                        // replace or set src to canonical placeholder
                        if (preg_match('/src=/', $tag)) {
                            $tag = preg_replace('/src=["\'][^"\']*["\']/', 'src="IMAGE_PLACEHOLDER_'.$token.'"', $tag);
                        } else {
                            $tag = str_replace('<img', '<img src="IMAGE_PLACEHOLDER_'.$token.'"', $tag);
                        }
                    }
                }
            }

            return $tag;
        }, $html);

        return $html;
    }

    /**
     * Extract images metadata from HTML. Supports HTML comment block <!-- IMAGES { ... } -->
     * and a machine-delimited block ===IMAGES_JSON=== ... ===END_IMAGES===.
     */
    private function extractImagesMetadataFromHtml(string $html): array
    {
        if (! $html) return [];

        // 1) Try HTML comment block
        if (preg_match('/<!--\s*IMAGES\s*(\{.*?\})\s*-->/s', $html, $m)) {
            $json = $m[1];
            $data = json_decode($json, true);
            if (is_array($data) && !empty($data['images'])) {
                return $data['images'];
            }
        }

        // 2) Try machine-delimited block
        if (preg_match('/===IMAGES_JSON===\s*(\{.*?\})\s*===END_IMAGES===/s', $html, $m2)) {
            $json = $m2[1];
            $data = json_decode($json, true);
            if (is_array($data) && !empty($data['images'])) {
                return $data['images'];
            }
        }

        // 3) Fallback: scan for <img data-image="token">
        $images = [];
        if (preg_match_all('/<img[^>]*data-image=["\']([a-zA-Z0-9_-]+)["\']/i', $html, $tokens)) {
            foreach ($tokens[1] as $tok) {
                $images[] = ['token' => $tok, 'prompt' => $tok, 'size' => '1024x1024'];
            }
        }

        return $images;
    }

    /**
     * A small local HTML stub for development when no external LLM is available.
     */
    private function stubHtmlForPrompt(string $prompt): string
    {
        $token = 'hero';
        $imagesJson = json_encode(['images' => [['token' => $token, 'prompt' => $prompt, 'size' => '1024x1024']]], JSON_PRETTY_PRINT);

        return "<!doctype html>\n<html><head><meta charset=\"utf-8\"><title>".htmlspecialchars($prompt)."</title></head><body>\n" .
               "<header><h1>".htmlspecialchars($prompt)."</h1></header>\n" .
               "<section><img src=\"IMAGE_PLACEHOLDER_{$token}\" data-image=\"{$token}\" alt=\"Hero\"></section>\n" .
               "<!-- IMAGES\n{$imagesJson}\n-->\n" .
               "</body></html>";
    }

    /**
     * Detect if HTML appears truncated (missing closing tags).
     */
    private function isHtmlTruncated(string $html): bool
    {
        return !preg_match('/<\/html>\s*$/i', trim($html));
    }

    /**
     * Request LLM continuation if HTML is truncated.
     */
    private function requestHtmlContinuation(string $html): string
    {
        $apiKey = config('services.openrouter.key');
        if (!$apiKey) {
            Log::warning('OpenRouter API key not configured, skipping continuation');
            return '';
        }

        $prompt = "Continue and complete this truncated HTML. Return only the missing fragment, no markdown fences or explanations:\n\n" . substr($html, -500);

        try {
            $resp = Http::timeout(30)->post(config('services.openrouter.base_url'), [
                'model' => 'openai/gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 500,
            ], [
                'Authorization' => 'Bearer ' . $apiKey,
            ]);

            if (!$resp->successful()) {
                Log::warning('Continuation LLM request failed', ['status' => $resp->status()]);
                return '';
            }

            $body = $resp->json();
            $cont = $body['choices'][0]['message']['content'] ?? '';
            $cont = preg_replace('/^```html\s*/i', '', $cont);
            $cont = str_replace('```', '', $cont);
            return trim($cont);
        } catch (\Exception $e) {
            Log::error('Continuation request error', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Request LLM to extract images metadata from HTML if not found in metadata blocks.
     */
    private function requestImagesMetaFromHtml(string $html): array
    {
        $apiKey = config('services.openrouter.key');
        if (!$apiKey) {
            Log::warning('OpenRouter API key not configured, skipping secondary extraction');
            return [];
        }

        $prompt = "Extract image tokens and prompts from this HTML. Return only valid JSON matching:\n" .
                  "{\"images\": [{\"token\": \"...\", \"prompt\": \"...\", \"size\": \"1024x1024\"}]}\n\n" . 
                  substr($html, 0, 2000);

        try {
            $resp = Http::timeout(30)->post(config('services.openrouter.base_url'), [
                'model' => 'openai/gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 800,
            ], [
                'Authorization' => 'Bearer ' . $apiKey,
            ]);

            if (!$resp->successful()) {
                Log::warning('Images extraction LLM request failed', ['status' => $resp->status()]);
                return [];
            }

            $body = $resp->json();
            $content = $body['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/```\s*$/i', '', $content);
            
            $data = json_decode($content, true);
            return $data['images'] ?? [];
        } catch (\Exception $e) {
            Log::error('Images extraction error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Postprocess HTML: append hidden image placeholders for any missing tokens,
     * add footer and FAQ script to ensure complete page structure.
     */
    private function postprocessHtml(string $html, array $images): string
    {
        if (empty($images)) {
            return $html;
        }

        // Collect tokens already in HTML
        preg_match_all('/<img[^>]*data-image=["\']([a-zA-Z0-9_-]+)["\']/i', $html, $tokens);
        $existingTokens = $tokens[1] ?? [];

        // Append hidden placeholders for missing tokens
        $hiddenPlaceholders = '';
        foreach ($images as $img) {
            $tok = $img['token'] ?? '';
            if ($tok && !in_array($tok, $existingTokens)) {
                $hiddenPlaceholders .= '<img src="IMAGE_PLACEHOLDER_'.$tok.'" data-image="'.$tok.'" alt="'.$tok.'" style="display:none;">';
            }
        }

        // Append minimal footer if missing
        $footer = '';
        if (!preg_match('/<footer/i', $html)) {
            $footer = '<footer style="padding:2rem;background:rgba(0,0,0,0.05);text-align:center;"><p>&copy; 2026. All rights reserved.</p></footer>';
        }

        // Append FAQ JavaScript if missing
        $faqScript = '';
        if (!preg_match('/accordion|faq.*script/i', $html)) {
            $faqScript = '<script>(function(){' .
                'const faqs = document.querySelectorAll(".faq-item, [role=\"tab\"]");' .
                'faqs.forEach(el => el.addEventListener("click", () => {' .
                  'const content = el.nextElementSibling;' .
                  'if(content) content.style.display = content.style.display === "none" ? "block" : "none";' .
                '}));' .
              '})()</script>';
        }

        // Insert before </body>
        $html = str_replace('</body>', $hiddenPlaceholders . $footer . $faqScript . '</body>', $html);

        // Ensure </html> exists
        if (!preg_match('/<\/html>/i', $html)) {
            $html .= '</html>';
        }

        return $html;
    }

    /**
     * Get the status of a project and its image jobs.
     * Returns the project status and a list of image jobs with their result URLs.
     */
    public function status(int $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Get all image jobs for this project
        $imagejobs = $project->imageJobs()->get();
        $jobs = $imagejobs->map(function ($job) {
            return [
                'token' => $job->token,
                'status' => $job->status,
                'result_url' => $job->result_url,
                'error' => $job->error,
            ];
        })->toArray();

        return response()->json([
            'project' => [
                'id' => $project->id,
                'status' => $project->status ?? 'processing',
                'html' => $project->html,
                'title' => $project->title,
            ],
            'jobs' => $jobs,
        ]);
    }

    /**
     * Edit a project with a conversational message.
     */
    public function edit(int $id, Request $request)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $message = $request->input('message', '');
        if (!$message) {
            return response()->json(['error' => 'Message required'], 400);
        }

        // TODO: Implement conversational edits with LLM
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'Edit request received (feature coming soon)',
        ]);
    }

    

    /**
     * Get chat messages for a project.
     */
    public function messages(int $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // TODO: Fetch actual messages from ProjectMessage model
        return response()->json([
            'project_id' => $id,
            'messages' => [],
        ]);
    }
}
