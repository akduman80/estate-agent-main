<?php
namespace App\Console\Commands;

use App\Models\{WhatsappOutbox, WhatsappSetting};
use App\Services\EvolutionClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;

class WhatsappWork extends Command
{
    protected $signature = 'whatsapp:work {--limit=20}';
    protected $description = 'Kalıcı yönetici WhatsApp bildirimlerini güvenli olarak işler';
    public function handle(EvolutionClient $client): int
    {
        // A worker that dies after sending cannot safely resend its claim.
        WhatsappOutbox::where('status','sending')->where('claimed_at','<',now()->subMinutes(3))
            ->update(['status'=>'uncertain','error_code'=>'worker_interrupted']);
        if (!WhatsappSetting::current()->enabled) return self::SUCCESS;
        $state=$client->state();
        WhatsappSetting::current()->update(['connection_state'=>$state,'checked_at'=>now()]);
        // Connection checks are read-only: during an outage work stays durable and unclaimed.
        if ($state!=='open') return self::SUCCESS;
        for ($i=0; $i<min(100,max(1,(int)$this->option('limit'))); $i++) {
            $job=DB::transaction(function () {
                $row=WhatsappOutbox::whereIn('status',['pending','retry'])->where('available_at','<=',now())->orderBy('id')->lockForUpdate()->first();
                if ($row) $row->update(['status'=>'sending','claimed_at'=>now(),'attempts'=>$row->attempts+1]);
                return $row;
            });
            if (!$job) break;
            try {
                $response=$client->request('POST','/message/sendText/'.$client->instancePath(), ['number'=>$job->recipient,'text'=>$job->payload['text']]);
                if ($response->successful()) {
                    $id=$response->json('key.id');
                    $job->update(['status'=>is_string($id)&&$id!==''?'accepted':'uncertain', 'provider_id'=>is_string($id)?substr($id,0,200):null,'error_code'=>null]);
                } elseif ($response->status()===429) {
                    $job->update(['status'=>$job->attempts<5?'retry':'failed','available_at'=>now()->addSeconds(min(3600,30 * (2 ** $job->attempts))), 'error_code'=>'rate_limited']);
                } elseif ($response->serverError() || $response->status()===408) {
                    $job->update(['status'=>'uncertain','error_code'=>'provider_ambiguous']);
                } else {
                    $job->update(['status'=>'failed','error_code'=>'http_'.$response->status()]);
                }
            } catch (ConnectionException) {
                $job->update(['status'=>'uncertain','error_code'=>'connection_result_unknown']);
            } catch (\LogicException) {
                $job->update(['status'=>'failed','error_code'=>'configuration']);
            } catch (\Throwable) {
                $job->update(['status'=>'uncertain','error_code'=>'unexpected_result_unknown']);
            }
        }
        // Keep unresolved work; erase terminal records containing personal data after 30 days.
        WhatsappOutbox::whereIn('status',['accepted','failed','cancelled'])->where('updated_at','<',now()->subDays(30))->delete();
        return self::SUCCESS;
    }
}
