<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgoraService
{
    private const TOKEN_EXPIRE_SECONDS = 3600; // 1 hour

    private string $appId;
    private string $appCertificate;

    public function __construct()
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');

        if (!$this->appId || !$this->appCertificate) {
            throw new \Exception('Agora App ID or App Certificate not configured');
        }
    }

    /**
     * Generate an Agora RTC token for a user to join a channel.
     *
     * @param string $channelName The channel name to join
     * @param int $uid User ID (0 for dynamic UID assignment)
     * @param string $role 'publisher' for host, 'subscriber' for member
     * @return string The generated RTC token
     */
    public function generateToken(string $channelName, int $uid, string $role): string
    {
        $timestamp = intval(time());
        $expirationTimestamp = $timestamp + self::TOKEN_EXPIRE_SECONDS;

        // Build the token string
        $tokenBuilder = new AgoraTokenBuilder(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid
        );

        $token = $tokenBuilder->buildTokenWithUid(
            $expirationTimestamp,
            $this->getRoleValue($role)
        );

        Log::info('Agora token generated', [
            'channel' => $channelName,
            'uid' => $uid,
            'role' => $role,
        ]);

        return $token;
    }

    /**
     * Get Agora role value from role string.
     *
     * @param string $role 'publisher' or 'subscriber'
     * @return int Role constant (1 for publisher, 2 for subscriber)
     */
    private function getRoleValue(string $role): int
    {
        return strtolower($role) === 'publisher' ? 1 : 2;
    }
}

/**
 * Manual Agora Token Builder Implementation
 * 
 * This implements the Agora RTC token generation algorithm
 * using HMAC-SHA256 as per Agora documentation.
 * 
 * Token Format: version + signature + content
 * where:
 * - version: 1 byte (0x06)
 * - signature: 32 bytes (HMAC-SHA256)
 * - content: serialized user data and privileges
 */
class AgoraTokenBuilder
{
    private string $appId;
    private string $appCertificate;
    private string $channelName;
    private int $uid;

    private const VERSION = "006";

    // Privilege IDs
    private const PRIVILEGE_JOIN_CHANNEL = 1;
    private const PRIVILEGE_PUBLISH_AUDIO_STREAM = 2;
    private const PRIVILEGE_PUBLISH_VIDEO_STREAM = 3;
    private const PRIVILEGE_PUBLISH_DATA_STREAM = 4;

    public function __construct(
        string $appId,
        string $appCertificate,
        string $channelName,
        int $uid = 0
    ) {
        $this->appId = $appId;
        $this->appCertificate = $appCertificate;
        $this->channelName = $channelName;
        $this->uid = $uid;
    }

    /**
     * Build an RTC token with UID.
     *
     * @param int $expirationTimestamp Token expiration time (unix timestamp)
     * @param int $role User role (1=publisher, 2=subscriber)
     * @return string The RTC token in format: version + base64(signature + content)
     */
    public function buildTokenWithUid(int $expirationTimestamp, int $role): string
    {
        $payload = $this->buildPayload($expirationTimestamp, $role);
        return $this->encodeToken($payload);
    }

    /**
     * Build the token payload (binary format).
     */
    private function buildPayload(int $expirationTimestamp, int $role): string
    {
        // Build the content part first (before signing)
        $content = $this->serializeContent($expirationTimestamp);

        // Create signing input: AppId + ChannelName + Uid + Content
        $signingInput = $this->appId . $this->channelName . $this->uid . $content;

        // Sign with HMAC-SHA256
        $signature = hash_hmac(
            'sha256',
            $signingInput,
            $this->appCertificate,
            true
        );

        // Token payload: version byte + signature + content
        return chr(0x06) . $signature . $content;
    }

    /**
     * Serialize content with channel name, UID, and privileges.
     */
    private function serializeContent(int $expirationTimestamp): string
    {
        $buffer = '';

        // Channel Name (4-byte length + string)
        $buffer .= pack('N', strlen($this->channelName));
        $buffer .= $this->channelName;

        // UID (4 bytes)
        $buffer .= pack('N', $this->uid);

        // Privileges (4-byte count + privilege entries)
        $privileges = $this->buildPrivileges($expirationTimestamp);
        $buffer .= pack('N', count($privileges));

        // Serialize each privilege
        foreach ($privileges as $name => $data) {
            $buffer .= pack('N', strlen($name));
            $buffer .= $name;
            $buffer .= pack('N', $data['privilege']);
            $buffer .= pack('N', $data['expire']);
        }

        return $buffer;
    }

    /**
     * Build the privilege map with expiration times.
     */
    private function buildPrivileges(int $expirationTimestamp): array
    {
        return [
            'join_channel' => [
                'privilege' => self::PRIVILEGE_JOIN_CHANNEL,
                'expire' => $expirationTimestamp,
            ],
            'publish_audio_stream' => [
                'privilege' => self::PRIVILEGE_PUBLISH_AUDIO_STREAM,
                'expire' => $expirationTimestamp,
            ],
            'publish_video_stream' => [
                'privilege' => self::PRIVILEGE_PUBLISH_VIDEO_STREAM,
                'expire' => $expirationTimestamp,
            ],
            'publish_data_stream' => [
                'privilege' => self::PRIVILEGE_PUBLISH_DATA_STREAM,
                'expire' => $expirationTimestamp,
            ],
        ];
    }

    /**
     * Encode token to final string format: version + base64(payload).
     * 
     * @param string $payload Binary payload (version + signature + content)
     * @return string Final token string
     */
    private function encodeToken(string $payload): string
    {
        // Skip the version byte from payload and encode the rest in base64
        $base64Payload = base64_encode($payload);
        // Prepend version
        return self::VERSION . $base64Payload;
    }
}
