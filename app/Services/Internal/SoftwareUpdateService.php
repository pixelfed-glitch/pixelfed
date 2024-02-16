<?php

namespace App\Services\Internal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SoftwareUpdateService
{
    const CACHE_KEY = 'pf:services:software-update:';

    public static function get()
    {
        $curVersion = config('pixelfed.version');

        $version = Cache::remember(self::CACHE_KEY . $curVersion, 1800, function() {
            return self::fetchLatest();
        });

        $version_regex = '/v*([0-9]+\.[0-9]+\.[0-9]+(?:-?[0-9a-zA-Z-\._]+)?(?:\+|-)glitch\.?([0-9]+\.[0-9]+\.[0-9](?:-?[0-9a-zA-Z-_]+)?))/';

        if(!$version || !isset($version['tag_name']) || !preg_match($version_regex, $version['tag_name'], $latest_matches)) {
            $hideWarning = (bool) config('instance.software-update.disable_failed_warning');
            return [
                'current' => $curVersion,
                'latest' => [
                    'version' => null,
                    'published_at' => null,
                    'url' => null,
                ],
                'running_latest' => $hideWarning ? true : null
            ];
        }

        preg_match($version_regex, $curVersion, $current_matches);

        $latest_version = $latest_matches[1];
        $glitch_latest = $latest_matches[2];
        $glitch_current = $current_matches[2];

        return [
            'current' => $curVersion,
            'latest' => [
                'version' => $latest_version,
                'published_at' => $version['published_at'],
                'url' => $version['html_url'],
            ],
            'running_latest' => version_compare($glitch_current, $glitch_latest, '>=')
        ];
    }

    public static function fetchLatest()
    {
        try {
            $res = Http::withOptions(['allow_redirects' => false])
                ->timeout(5)
                ->connectTimeout(5)
                ->retry(2, 500)
                ->get('https://api.github.com/repos/pixelfed-glitch/pixelfed/releases/latest');
        } catch (RequestException $e) {
            return;
        } catch (ConnectionException $e) {
            return;
        } catch (Exception $e) {
            return;
        }

        if(!$res->ok()) {
            return;
        }

        return $res->json();
    }
}
