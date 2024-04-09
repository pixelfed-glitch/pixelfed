<?php

namespace App\Services\Internal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class BeagleService
{
    const DEFAULT_RULES_CACHE_KEY = 'pf:services:beagle:default_rules:v1';

    public static function getDefaultRules()
    {
        return Cache::remember(self::DEFAULT_RULES_CACHE_KEY, now()->addDays(7), function() {
            try {
                $res = Http::withOptions(['allow_redirects' => false])
                    ->timeout(5)
                    ->connectTimeout(5)
                    ->retry(2, 500)
                    ->get('https://beagle.pixelfed.net/api/v1/common/suggestions/rules');
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

            $json = $res->json();

            if(!isset($json['rule_suggestions']) || !count($json['rule_suggestions'])) {
                return [];
            }
            return $json['rule_suggestions'];
        });
    }

}
