<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [
        'name' => env('APP_NAME', 'NBPDCL-SaaS'),

        'source' => [
            'files' => [
                'include' => [
                    base_path('app'),
                    base_path('config'),
                    base_path('database'),
                    base_path('resources'),
                    base_path('routes'),
                    storage_path('app/bills'),
                ],

                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    storage_path('framework'),
                    storage_path('logs'),
                    storage_path('app/backups'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            'databases' => [
                config('database.default', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_extension' => '',

        'destination' => [
            'filename_prefix' => 'spatie_backup_',
            'disks' => [
                'local',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@nbpdcl-saas.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'system@nbpdcl-saas.com'),
                'name' => env('MAIL_FROM_NAME', 'NBPDCL Backup System'),
            ],
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'NBPDCL-SaaS'),
            'disks' => ['local'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 10000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
