<?php

namespace App\Console\Commands;

use App\Actions\CreatePendingRant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZBateson\MailMimeParser\MailMimeParser;

class IngestRants extends Command
{
    protected $signature = 'rants:ingest {path : A .eml file or a directory of .eml files}';

    protected $description = 'Ingest rant emails from local .eml files (offline dev pipeline)';

    public function handle(CreatePendingRant $intake): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Path not found: {$path}");

            return self::FAILURE;
        }

        $files = is_dir($path)
            ? File::glob(rtrim($path, '/').'/*.eml')
            : [$path];

        if (empty($files)) {
            $this->warn('No .eml files found.');

            return self::SUCCESS;
        }

        $parser = new MailMimeParser;
        $count = 0;

        foreach ($files as $file) {
            $message = $parser->parse(fopen($file, 'r'), true);

            $from = $message->getHeaderValue('from');
            $subject = $message->getHeaderValue('subject');
            $body = $message->getTextContent() ?? strip_tags((string) $message->getHtmlContent());

            if (trim((string) $body) === '') {
                $this->warn("Skipped (empty body): {$file}");

                continue;
            }

            $rant = $intake->fromEmail($from, $subject, trim($body));
            $this->info("Ingested #{$rant->id} from ".basename($file)." → pending, scrub queued");
            $count++;
        }

        $this->info("Done. {$count} rant(s) ingested.");

        return self::SUCCESS;
    }
}
