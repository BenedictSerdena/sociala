<?php

namespace App\Services;

class AgoraToken
{
    const ROLE_PUBLISHER  = 1;
    const ROLE_SUBSCRIBER = 2;

    const PRIV_JOIN_CHANNEL    = 1;
    const PRIV_PUBLISH_AUDIO   = 2;
    const PRIV_PUBLISH_VIDEO   = 3;
    const PRIV_PUBLISH_DATA    = 4;

    public static function buildRtcToken(
        string $appId,
        string $appCertificate,
        string $channelName,
        int    $uid,
        int    $role = self::ROLE_PUBLISHER,
        int    $expireSeconds = 3600
    ): string {
        $expireTs = time() + $expireSeconds;
        $uid_str  = $uid === 0 ? '' : (string) $uid;

        // Build privileges message
        $privileges = [self::PRIV_JOIN_CHANNEL => $expireTs];
        if ($role === self::ROLE_PUBLISHER) {
            $privileges[self::PRIV_PUBLISH_AUDIO] = $expireTs;
            $privileges[self::PRIV_PUBLISH_VIDEO] = $expireTs;
            $privileges[self::PRIV_PUBLISH_DATA]  = $expireTs;
        }

        $salt = rand(1, 99999999);
        $ts   = time() + 24 * 3600;

        $msg  = pack('NN', $salt, $ts);
        $msg .= pack('n', count($privileges));
        foreach ($privileges as $k => $v) {
            $msg .= pack('nN', $k, $v);
        }

        $toSign = $appId . $channelName . $uid_str . $msg;
        $sig    = hash_hmac('sha256', $toSign, $appCertificate, true);

        $crcChannel = crc32($channelName) & 0xffffffff;
        $crcUid     = crc32($uid_str)     & 0xffffffff;

        $content = self::packString($sig) . pack('NN', $crcChannel, $crcUid) . $msg;

        return '006' . $appId . base64_encode($content);
    }

    private static function packString(string $s): string
    {
        return pack('n', strlen($s)) . $s;
    }
}
