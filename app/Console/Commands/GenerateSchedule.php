<?php

namespace App\Console\Commands;

use App\Enums\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $path = storage_path('app/public/');
        $fileName = 'schedule.csv';

        $types = Activity::getValues();
        $startTime = Carbon::parse('9:00');
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addYear();

        $file = fopen($path.$fileName, 'w');
        fputcsv($file, ['Date', 'Start time', 'End time', 'Type']);

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            for ($i = 0; $i < 9; $i++) {
                $startTimeStr = $startTime->format('H:i');
                $endTimeStr = $startTime->addHour()->format('H:i');
                $type = $types[array_rand($types)];

                fputcsv($file, [$date->format('Y-m-d'), $startTimeStr, $endTimeStr, $type]);
            }

            $startTime->setTime(9, 0);
        }

        fclose($file);
    }
}
