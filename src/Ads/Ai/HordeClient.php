<?php

namespace Packitifotech\Laragdads\Ads\Ai;

use GdImage;
use RuntimeException;

/**
 * Minimal client for the AI Horde (Stable Horde) image API: https://aihorde.net/api/
 *
 * Flow: submit an async job, poll until it is done, download the result. The API key is never stored by
 * this class: pass it in, or set the AIHORDE_API_KEY environment variable / `aihorde.key` Laravel config.
 * The anonymous key "0000000000" works too, with the lowest queue priority.
 */
final class HordeClient
{
    public const BASE_URL = 'https://aihorde.net/api/v2';

    public function __construct(
        private ?string $apiKey = null,
        private string $baseUrl = self::BASE_URL,
        private string $clientAgent = 'laragdads:1.0:https://github.com/tusharsawant2427/laravelgdads',
        private int $timeoutSeconds = 240,
        private int $pollSeconds = 4
    ) {
        $this->apiKey ??= (getenv('AIHORDE_API_KEY') ?: '0000000000');
    }

    /**
     * Generate one image and return it as a GdImage.
     *
     * @param array{steps?:int,cfg_scale?:float,sampler_name?:string,models?:string[],seed?:string,negative?:string,nsfw?:bool,karras?:bool} $options
     */
    public function generate(string $prompt, int $width, int $height, array $options = []): GdImage
    {
        [$width, $height] = [self::snap($width), self::snap($height)];

        $negative = $options['negative'] ?? 'text, letters, words, watermark, logo, signature, blurry, low quality, deformed';
        $payload = [
            'prompt' => $prompt . ' ### ' . $negative,
            'params' => array_filter([
                'sampler_name' => $options['sampler_name'] ?? 'k_euler_a',
                'cfg_scale' => $options['cfg_scale'] ?? 7.0,
                'steps' => $options['steps'] ?? 25,
                'width' => $width,
                'height' => $height,
                'n' => 1,
                'karras' => $options['karras'] ?? true,
                'seed' => $options['seed'] ?? null,
            ], fn ($v) => $v !== null),
            'nsfw' => (bool) ($options['nsfw'] ?? false),
            'censor_nsfw' => true,
            'slow_workers' => true,
            'r2' => true,
        ];
        if (!empty($options['models'])) {
            $payload['models'] = array_values($options['models']);
        }

        $job = $this->request('POST', '/generate/async', $payload);
        $id = $job['id'] ?? throw new RuntimeException('AI Horde did not return a job id.');

        $deadline = time() + $this->timeoutSeconds;
        while (true) {
            $check = $this->request('GET', "/generate/check/{$id}");
            if (!empty($check['faulted'])) {
                throw new RuntimeException('AI Horde reported the job as faulted.');
            }
            if (!empty($check['done'])) {
                break;
            }
            if (time() >= $deadline) {
                $this->request('DELETE', "/generate/status/{$id}");   // free the queue slot
                throw new RuntimeException("AI Horde job timed out after {$this->timeoutSeconds}s (queue position " . ($check['queue_position'] ?? '?') . ').');
            }
            sleep(max(2, min($this->pollSeconds, (int) ($check['wait_time'] ?? $this->pollSeconds))));
        }

        $status = $this->request('GET', "/generate/status/{$id}");
        $generation = $status['generations'][0] ?? throw new RuntimeException('AI Horde finished without an image.');
        if (!empty($generation['censored'])) {
            throw new RuntimeException('AI Horde censored the image.');
        }

        return $this->decode($generation['img']);
    }

    /** Horde sizes must be multiples of 64. */
    public static function snap(int $px): int
    {
        return max(64, min(3072, (int) (round($px / 64) * 64)));
    }

    // ----------------------------------------------------------------- http

    private function request(string $method, string $path, ?array $json = null): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->apiKey,
                'Client-Agent: ' . $this->clientAgent,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        if ($json !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE));
        }

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException("AI Horde request failed: {$error}");
        }
        $data = json_decode((string) $body, true);
        if ($code >= 400) {
            throw new RuntimeException("AI Horde {$method} {$path} returned HTTP {$code}: " . ($data['message'] ?? substr((string) $body, 0, 200)));
        }

        return is_array($data) ? $data : [];
    }

    private function decode(string $img): GdImage
    {
        if (str_starts_with($img, 'http')) {
            $ch = curl_init($img);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60]);
            $bytes = curl_exec($ch);
            curl_close($ch);
        } else {
            $bytes = base64_decode($img, true);   // r2 disabled: the API returns base64 webp
        }

        $image = $bytes ? @imagecreatefromstring($bytes) : false;
        if ($image === false) {
            throw new RuntimeException('Could not decode the image returned by AI Horde.');
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        return $image;
    }
}
