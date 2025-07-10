<?php

namespace App\Console\Commands;

use App\Media;
use App\Models\ImportPost;
use App\Profile;
use App\Services\AccountService;
use App\Services\ImportService;
use App\Services\MediaPathService;
use App\Status;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Storage;

class TransformImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transform-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform imports into statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! config('import.instagram.enabled')) {
            return;
        }

        $ips = ImportPost::whereNull('status_id')->where('skip_missing_media', '!=', true)->take(1500)->get();

        if (! $ips->count()) {
            return;
        }

        foreach ($ips as $ip) {
            $id = $ip->user_id;
            $pid = $ip->profile_id;
            $profile = Profile::find($pid);
            if (! $profile) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $exists = ImportPost::whereUserId($id)
                ->whereNotNull('status_id')
                ->where('filename', $ip->filename)
                ->where('creation_year', $ip->creation_year)
                ->where('creation_month', $ip->creation_month)
                ->where('creation_day', $ip->creation_day)
                ->exists();

            if ($exists == true) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $idk = ImportService::getId($ip->user_id, $ip->creation_year, $ip->creation_month, $ip->creation_day);
            if (! $idk) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $originalIncr = $idk['incr'];
            $attempts = 0;
            while ($attempts < 999) {
                $duplicateCheck = ImportPost::where('user_id', $id)
                    ->where('creation_year', $ip->creation_year)
                    ->where('creation_month', $ip->creation_month)
                    ->where('creation_day', $ip->creation_day)
                    ->where('creation_id', $idk['incr'])
                    ->where('id', '!=', $ip->id)
                    ->exists();

                if (! $duplicateCheck) {
                    break;
                }

                $idk['incr']++;
                $attempts++;

                if ($idk['incr'] > 999) {
                    $this->warn("Could not find unique creation_id for ImportPost ID {$ip->id} on {$ip->creation_year}-{$ip->creation_month}-{$ip->creation_day}");
                    $ip->skip_missing_media = true;
                    $ip->save();

                    continue 2;
                }
            }

            if ($attempts >= 999) {
                $this->warn("Exhausted attempts finding unique creation_id for ImportPost ID {$ip->id}");
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            if ($idk['incr'] !== $originalIncr) {
                $uid = str_pad($id, 6, 0, STR_PAD_LEFT);
                $yearStr = str_pad($ip->creation_year, 2, 0, STR_PAD_LEFT);
                $monthStr = str_pad($ip->creation_month, 2, 0, STR_PAD_LEFT);
                $dayStr = str_pad($ip->creation_day, 2, 0, STR_PAD_LEFT);
                $zone = $yearStr.$monthStr.$dayStr.str_pad($idk['incr'], 3, 0, STR_PAD_LEFT);
                $idk['id'] = '1'.$uid.$zone;

                $this->info("Adjusted creation_id from {$originalIncr} to {$idk['incr']} for ImportPost ID {$ip->id}");
            }

            if (Storage::exists('imports/'.$id.'/'.$ip->filename) === false) {
                ImportService::clearAttempts($profile->id);
                ImportService::getPostCount($profile->id, true);
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $missingMedia = false;
            foreach ($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $og = 'imports/'.$id.'/'.$fileName;
                if (! Storage::exists($og)) {
                    $missingMedia = true;
                }
            }

            if ($missingMedia === true) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $caption = $ip->caption ?? '';
            $status = new Status;
            $status->profile_id = $pid;
            $status->caption = $caption;
            $status->type = $ip->post_type;

            $status->scope = 'public';
            $status->visibility = 'public';
            $status->id = $idk['id'];
            $status->created_at = now()->parse($ip->creation_date);
            $status->saveQuietly();

            foreach ($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $ext = last(explode('.', $fileName));
                $basePath = MediaPathService::get($profile);
                $og = 'imports/'.$id.'/'.$fileName;
                if (! Storage::exists($og)) {
                    $ip->skip_missing_media = true;
                    $ip->save();

                    continue;
                }
                $size = Storage::size($og);
                $mime = Storage::mimeType($og);
                $newFile = Str::random(40).'.'.$ext;
                $np = $basePath.'/'.$newFile;
                Storage::move($og, $np);
                $media = new Media;
                $media->profile_id = $pid;
                $media->user_id = $id;
                $media->status_id = $status->id;
                $media->media_path = $np;
                $media->mime = $mime;
                $media->size = $size;
                $media->save();
            }

            try {
                DB::transaction(function () use ($ip, $status, $profile, $idk) {
                    $ip->status_id = $status->id;
                    $ip->creation_id = $idk['incr'];
                    $ip->save();

                    $profile->status_count = $profile->status_count + 1;
                    $profile->save();
                });

                AccountService::del($profile->id);
                ImportService::clearAttempts($profile->id);
                ImportService::getPostCount($profile->id, true);

            } catch (QueryException $e) {
                if ($e->getCode() === '23000') {
                    $this->warn("Constraint violation for ImportPost ID {$ip->id}: ".$e->getMessage());
                    $ip->skip_missing_media = true;
                    $ip->save();

                    Media::where('status_id', $status->id)->delete();
                    $status->delete();

                    continue;
                } else {
                    throw $e;
                }
            }
        }
    }
}
