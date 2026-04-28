<?php

namespace App\Http\Controllers;

use App\Events\CallSignal;
use App\Models\User;
use App\Services\AgoraToken;
use Illuminate\Http\Request;

class CallController extends Controller
{
    private string $appId          = '';
    private string $appCertificate = '';

    public function __construct()
    {
        $this->appId          = env('AGORA_APP_ID');
        $this->appCertificate = env('AGORA_APP_CERTIFICATE');
    }

    public function token(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
        ]);

        $uid   = auth()->id();
        $token = AgoraToken::buildRtcToken(
            $this->appId,
            $this->appCertificate,
            $request->channel,
            $uid,
            AgoraToken::ROLE_PUBLISHER,
            3600
        );

        return response()->json([
            'token'   => $token,
            'uid'     => $uid,
            'app_id'  => $this->appId,
            'channel' => $request->channel,
        ]);
    }

    public function signal(Request $request)
    {
        $request->validate([
            'to'          => 'required|integer|exists:users,id',
            'type'        => 'required|in:incoming,accepted,declined,ended',
            'call_type'   => 'required|in:voice,video',
            'channel'     => 'required|string',
        ]);

        $caller = auth()->user();

        broadcast(new CallSignal(
            toUserId:    $request->to,
            type:        $request->type,
            callType:    $request->call_type,
            channelName: $request->channel,
            caller:      [
                'id'         => $caller->id,
                'name'       => $caller->name,
                'username'   => $caller->username,
                'avatar_url' => $caller->avatar_url,
            ],
        ));

        return response()->json(['ok' => true]);
    }
}
