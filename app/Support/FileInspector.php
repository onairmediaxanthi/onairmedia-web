<?php

namespace App\Support;

/**
 * Διαβάζει τα βασικά τεχνικά στοιχεία ενός αρχείου υλικού, χωρίς εξωτερικά εργαλεία:
 * MP3 (kbps, kHz, κανάλια, διάρκεια), WAV (kHz, bit, κανάλια, διάρκεια), εικόνες (διαστάσεις).
 */
final class FileInspector
{
    /** @return array<string, mixed> */
    public static function inspect(string $path, string $ext): array
    {
        $ext = strtolower($ext);
        try {
            return match (true) {
                $ext === 'mp3' => self::mp3($path),
                $ext === 'wav' => self::wav($path),
                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => self::image($path),
                default => [],
            };
        } catch (\Throwable) {
            return [];
        }
    }

    public static function image(string $path): array
    {
        $s = @getimagesize($path);

        return $s ? ['width' => (int) $s[0], 'height' => (int) $s[1]] : [];
    }

    public static function wav(string $path): array
    {
        $h = fopen($path, 'rb');
        $head = fread($h, 12);
        if (strlen($head) < 12 || substr($head, 0, 4) !== 'RIFF' || substr($head, 8, 4) !== 'WAVE') {
            fclose($h);

            return [];
        }
        $out = [];
        $byteRate = 0;
        while (! feof($h)) {
            $ch = fread($h, 8);
            if (strlen($ch) < 8) {
                break;
            }
            $id = substr($ch, 0, 4);
            $size = unpack('V', substr($ch, 4, 4))[1];
            if ($id === 'fmt ') {
                $fmt = fread($h, $size);
                $f = unpack('vformat/vchannels/Vrate/Vbyterate/vblock/vbits', substr($fmt, 0, 16));
                $out += ['channels' => $f['channels'], 'sample_rate' => $f['rate'], 'bits' => $f['bits']];
                $byteRate = $f['byterate'];
            } elseif ($id === 'data') {
                if ($byteRate > 0) {
                    $out['seconds'] = round($size / $byteRate, 2);
                }
                break;
            } else {
                fseek($h, $size + ($size % 2), SEEK_CUR);
            }
        }
        fclose($h);

        return $out;
    }

    public static function mp3(string $path): array
    {
        $size = filesize($path);
        $h = fopen($path, 'rb');
        $buf = fread($h, 262144);
        fclose($h);

        // Παράκαμψη ID3v2
        $offset = 0;
        if (substr($buf, 0, 3) === 'ID3' && strlen($buf) >= 10) {
            $b = array_map('ord', str_split(substr($buf, 6, 4)));
            $offset = 10 + (($b[0] & 0x7F) << 21 | ($b[1] & 0x7F) << 14 | ($b[2] & 0x7F) << 7 | ($b[3] & 0x7F));
        }

        $rates = [
            1 => [1 => [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448], 2 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384], 3 => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320]],
            2 => [1 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256], 2 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160], 3 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160]],
        ];
        $srates = [1 => [44100, 48000, 32000], 2 => [22050, 24000, 16000], 25 => [11025, 12000, 8000]];

        $len = strlen($buf);
        for ($i = $offset; $i < $len - 4; $i++) {
            if (ord($buf[$i]) !== 0xFF || (ord($buf[$i + 1]) & 0xE0) !== 0xE0) {
                continue;
            }
            $b1 = ord($buf[$i + 1]);
            $b2 = ord($buf[$i + 2]);
            $b3 = ord($buf[$i + 3]);
            $verBits = ($b1 >> 3) & 3;           // 3=MPEG1, 2=MPEG2, 0=MPEG2.5
            $layerBits = ($b1 >> 1) & 3;         // 1=Layer III
            $brIdx = ($b2 >> 4) & 15;
            $srIdx = ($b2 >> 2) & 3;
            if ($verBits === 1 || $layerBits === 0 || $brIdx === 0 || $brIdx === 15 || $srIdx === 3) {
                continue;
            }
            $ver = $verBits === 3 ? 1 : ($verBits === 2 ? 2 : 25);
            $layer = 4 - $layerBits;
            $kbps = $rates[$ver === 1 ? 1 : 2][$layer][$brIdx];
            $sr = $srates[$ver][$srIdx];
            $channels = (($b3 >> 6) & 3) === 3 ? 1 : 2;
            $spf = $layer === 1 ? 384 : (($layer === 3 && $ver !== 1) ? 576 : 1152);

            // Xing/Info (VBR): πλήθος frames για σωστή διάρκεια και μέσο bitrate
            $sideInfo = $ver === 1 ? ($channels === 1 ? 17 : 32) : ($channels === 1 ? 9 : 17);
            $x = $i + 4 + $sideInfo;
            $tag = substr($buf, $x, 4);
            $seconds = null;
            if (($tag === 'Xing' || $tag === 'Info') && (ord($buf[$x + 7]) & 1)) {
                $frames = unpack('N', substr($buf, $x + 8, 4))[1];
                $seconds = $frames * $spf / $sr;
                if ($tag === 'Xing' && $seconds > 0) {
                    $kbps = (int) round(($size - $i) * 8 / $seconds / 1000);
                }
            } elseif (substr($buf, $i + 36, 4) === 'VBRI') {
                $frames = unpack('N', substr($buf, $i + 36 + 14, 4))[1];
                $seconds = $frames * $spf / $sr;
                $kbps = $seconds > 0 ? (int) round(($size - $i) * 8 / $seconds / 1000) : $kbps;
            }
            $seconds ??= ($size - $i) * 8 / ($kbps * 1000);

            return ['kbps' => $kbps, 'sample_rate' => $sr, 'channels' => $channels, 'seconds' => round($seconds, 2)];
        }

        return [];
    }
}
