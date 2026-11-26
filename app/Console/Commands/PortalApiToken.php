<?php

namespace App\Console\Commands;

use App\Models\Portal;
use Illuminate\Console\Command;

class PortalApiToken extends Command
{
    protected $signature = 'portal:api-token {portal : Portal id} {--revoke : Remove the token instead of creating one}';
    protected $description = 'Create (or revoke) the public API token of one portal';

    public function handle(): int
    {
        $portal = Portal::find($this->argument('portal'));

        if (!$portal) {
            $this->error('Portal not found.');
            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $portal->revokeApiToken();
            $this->info("API token of \"{$portal->portal_name}\" revoked.");
            return self::SUCCESS;
        }

        $token = $portal->issueApiToken();

        $this->info("New API token for \"{$portal->portal_name}\" (id {$portal->id}). Copy it now - it cannot be shown again:");
        $this->line($token);

        return self::SUCCESS;
    }
}
