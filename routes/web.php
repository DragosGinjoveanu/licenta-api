<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


Route::get('/debug-reverb', function () {
    try {
        $pusher = Broadcast::driver('reverb')->getPusher();
        
        $response = $pusher->trigger('test-channel', 'test-event', ['mesaj' => 'Bypass total!']);
        
        return response()->json([
            'status' => 'Cererea a fost expediată forțat!',
            'rezultat_pusher' => $response
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'Lovitură ratată!',
            'eroare' => $e->getMessage()
        ], 500);
    }
});

require __DIR__.'/auth.php';
