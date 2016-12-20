<?php

namespace App\Listeners;

use App\Events\Obyx;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ObyxListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Obyx  $event
     * @return void
     */
    public function handle(Obyx $event)
    {
        $obyx = \DB::table('obyx')
            ->where('reason', '=', $event->reason)
            ->first();

        \DB::table('user_obyx')->insert([
            'user_id' => $event->user_id,
            'obyx_id' => $obyx->id,
            'created_at' => Carbon::now(),
        ]);
    }
}