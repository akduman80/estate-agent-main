<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class EvolutionClient
{
    public function request(string $method, string $path, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = config('whatsapp.url');
        // Keep the privileged API credential on the local server only.
        if (!in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true) || !config('whatsapp.key')) {
            throw new \LogicException('whatsapp_configuration');
        }
        return Http::withHeaders(['apikey'=>config('whatsapp.key')])->acceptJson()
            ->connectTimeout(3)->timeout(12)->send($method, rtrim($url,'/').$path, ['json'=>$data]);
    }
    public function instancePath(): string { return rawurlencode(config('whatsapp.instance')); }
    public function state(): string
    {
        try {
            $r=$this->request('GET', '/instance/connectionState/'.$this->instancePath());
            return $r->successful() && in_array($r->json('instance.state'), ['open','close','connecting'], true) ? $r->json('instance.state') : 'unavailable';
        } catch (\Throwable) { return 'unavailable'; }
    }
}
