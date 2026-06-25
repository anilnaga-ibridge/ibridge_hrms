<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Take backup of database and save it on storage folder';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()

    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        $randomId   = Str::random(8);
        $todayDate  = Carbon::now()->format('Y-m-d');
        $todayTime  = Carbon::now()->format('H:i:s');
        $path       = Storage::disk('backup')->path('');

        $filename = $randomId . '--backup--' . $todayDate . '--' . $todayTime . '.sql';
        $outputFile = $path . $filename;

        // Escape the executable path to prevent metacharacter injection from
        // a misconfigured DUMP_PATH env value.
        $dumpPath = escapeshellcmd(env('DUMP_PATH', 'mysqldump'));

        // Each argument is individually escaped so that credentials or hostnames
        // containing spaces, quotes, dollar signs, or other shell metacharacters
        // cannot corrupt the command or be used for shell injection.
        // escapeshellarg() correctly handles both empty and non-empty strings,
        // so a single branch replaces the previous if/else.
        $command = implode(' ', [
            $dumpPath,
            '--user='     . escapeshellarg(env('DB_USERNAME', '')),
            '--password=' . escapeshellarg(env('DB_PASSWORD', '')),
            '--host='     . escapeshellarg(env('DB_HOST', '127.0.0.1')),
            '--port='     . escapeshellarg(env('DB_PORT', '3306')),
            escapeshellarg(env('DB_DATABASE', '')),
            '>',
            escapeshellarg($outputFile),
        ]);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        exec($command);
    }
}
