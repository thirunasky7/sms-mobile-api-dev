<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery is handled by the Android native foreground service
 * polling POST /api/v1/devices/heartbeat — no FCM required.
 */
class PushOutgoingSmsToDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Message $message)
    {
    }

    public function handle(): void
    {
        $this->message->loadMissing('device');

        if (! $this->message->device) {
            $this->message->update([
                'status' => 'queued',
                'error_message' => 'No device assigned',
            ]);

            return;
        }

        // Keep queued/sending for the phone's native poller.
        Log::info('SMS queued for native device poll', [
            'message_id' => $this->message->id,
            'device_id' => $this->message->device_id,
        ]);
    }
}
