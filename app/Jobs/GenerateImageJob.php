<?php

namespace App\Jobs;

use App\Models\ImageJob;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $imageJobId;
    protected Project $project;
    protected string $prompt;
    protected string $token;
    public int $timeout = 180;
    public $tries = 3; // retry up to 3 times if fails

    public function __construct(int $imageJobId)
    {
        $this->imageJobId = $imageJobId;

        $imageJob = ImageJob::findOrFail($imageJobId);
        $this->project = Project::findOrFail($imageJob->project_id);
        $this->prompt = $imageJob->prompt;
        $this->token = $imageJob->token;
    }

    public function handle()
    {
        $imageJob = ImageJob::findOrFail($this->imageJobId);
        $project  = Project::findOrFail($imageJob->project_id);

        try {
            $client = new \GuzzleHttp\Client();

            $accountId = env('CLOUDFLARE_ACCOUNT_ID');
            $token     = env('CLOUDFLARE_API_TOKEN');

            $url = "https://api.cloudflare.com/client/v4/accounts/$accountId/ai/run/@cf/stabilityai/stable-diffusion-xl-base-1.0";

            // Try generating image with Cloudflare AI
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'image/png',
                ],
                'json' => [
                    'prompt' => $imageJob->prompt,
                ],
                'timeout' => 60,
            ]);

            $imageBinary = $response->getBody()->getContents();

            // Fallback if Cloudflare returns empty
            if (empty($imageBinary)) {
                $imageBinary = $this->getUnsplashImage($imageJob->prompt);
            }

            $path = "projects/{$project->id}/images/{$imageJob->token}.png";
            Storage::disk('public')->put($path, $imageBinary);

            // $imageUrl = Storage::url($path);
            $imageUrl = url("/storage/{$path}");

            $this->updateProjectHtmlAndImages($project, $imageJob->token, $imageUrl);

            $imageJob->update([
                'status' => 'done',
                'result_url' => $imageUrl,
            ]);
        } catch (\Throwable $e) {
            // Unsplash fallback if Cloudflare AI fails completely
            try {
                $imageBinary = $this->getUnsplashImage($imageJob->prompt);

                $path = "projects/{$project->id}/images/{$imageJob->token}.png";
                Storage::disk('public')->put($path, $imageBinary);

                // $imageUrl = Storage::url($path);
                $imageUrl = url("/storage/{$path}");

                $this->updateProjectHtmlAndImages($project, $imageJob->token, $imageUrl);

                $imageJob->update([
                    'status' => 'done',
                    'result_url' => $imageUrl,
                ]);
            } catch (\Throwable $e2) {
                // If both fail, mark job as failed
                $imageJob->update(['status' => 'failed']);
                Log::error('Image generation failed completely', [
                    'image_job_id' => $imageJob->token,
                    'error' => $e2->getMessage(),
                ]);
            }
        }
    }

    /**
     * Fetch image from Unsplash as fallback
     */
    private function getUnsplashImage(string $query): string
    {
        $accessKey = env('UNSPLASH_ACCESS_KEY');
        $query = urlencode($query);
        $url = "https://api.unsplash.com/photos/random?query={$query}&client_id={$accessKey}";

        $data = json_decode(file_get_contents($url), true);

        $imageUrl = $data['urls']['regular'] ?? null;

        if (!$imageUrl) {
            throw new \Exception("Unsplash did not return an image for query: $query");
        }

        // Download image binary
        return file_get_contents($imageUrl);
    }



    /**
     * Updates project images and html when an image is generated.
     *
     * Robustly replaces <img> tags with matching data-image tokens
     * and updates the images array in the project.
     *
     * If all images are done, sets the project status to 'ready'.
     *
     * @param Project $project
     * @param string $token
     * @param string $imageUrl
     */
    private function updateProjectHtmlAndImages(Project $project, string $token, string $imageUrl): void
    {
        $images = $project->images ?? [];

        foreach ($images as &$img) {
            if (($img['token'] ?? null) === $token) {
                $img['url'] = $imageUrl;
                $img['status'] = 'done';
            }
        }

        $html = $project->html ?? '';

        /**
         * ✅ Robust replacement:
         * - Finds <img> with matching data-image token
         * - Replaces src no matter attribute order
         */
        $html = preg_replace_callback(
            '/<img\b[^>]*data-image=["\']' . preg_quote($token, '/') . '["\'][^>]*>/i',
            function ($matches) use ($imageUrl) {
                return preg_replace(
                    '/src=["\'][^"\']*["\']/i',
                    'src="' . $imageUrl . '"',
                    $matches[0]
                );
            },
            $html
        );

        $allDone = collect($images)->every(fn($img) => ($img['status'] ?? '') === 'done');

        $project->update([
            'images' => $images,
            'html'   => $html,
            'status' => $allDone ? 'ready' : $project->status,
        ]);
    }
}
