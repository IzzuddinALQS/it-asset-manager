<?php

namespace App\Console\Commands;

use App\Actions\Assets\ImportAssetsFromCsv;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('assets:import {file : Path to the CSV exported from the asset spreadsheet}')]
#[Description('Import deployed assets, their holders and accessories from a CSV file')]
class ImportAssetsCommand extends Command
{
    public function handle(ImportAssetsFromCsv $importAssets): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File {$path} tidak ditemukan.");

            return self::FAILURE;
        }

        $result = $importAssets->handle($path);

        $this->info("{$result['imported']} inventaris diimpor, {$result['skipped']} baris dilewati.");

        return self::SUCCESS;
    }
}
