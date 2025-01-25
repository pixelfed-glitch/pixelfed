<?php

namespace App\Console\Commands;

use App\Services\ConfigCacheService;
use Cache;
use DB;
use Illuminate\Console\Command;
use Storage;

class InstanceUpdateTotalLocalPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:instance-update-total-local-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the total number of local statuses/post count';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cached = $this->checkForCache();
        if (! $cached) {
            $this->initCache();

            return;
        }
        $cache = $this->getCached();
        if (! $cache || ! isset($cache['count']) || ! isset($cache['realCount'])) {
            // If we get an error on the cache, regenerate it
            $this->initCache();
        } else {
            $this->updateAndCache();
        }
        Cache::forget('api:nodeinfo');
    }

    protected function checkForCache()
    {
        return Storage::exists('total_local_posts.json');
    }

    protected function initCache()
    {
        $this->updateAndCache();
    }

    protected function getCached()
    {
        return Storage::json('total_local_posts.json');
    }

    protected function updateAndCache()
    {
        $count = DB::table('statuses')->whereNull(['url', 'deleted_at'])->count();
        $realCount = DB::table('statuses')->whereNull(['url', 'in_reply_to_id', 'reblog_of_id', 'deleted_at'])->count();
        $res = [
            'count' => $count,
            'realCount' => $realCount,
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);
        ConfigCacheService::put('instance.stats.real_total_local_posts', $res['realCount']);
    }
}
