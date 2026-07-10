<?php

namespace Tests\Unit\Models;

use App\Enums\EventSeverity;
use App\Enums\BackupStatus;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_event_severity_enum_values(): void
    {
        $this->assertSame('success', EventSeverity::SUCCESS->value);
        $this->assertSame('info', EventSeverity::INFO->value);
        $this->assertSame('warning', EventSeverity::WARNING->value);
        $this->assertSame('critical', EventSeverity::CRITICAL->value);
    }

    public function test_event_severity_has_icon(): void
    {
        $this->assertNotEmpty(EventSeverity::CRITICAL->icon());
    }

    public function test_backup_status_enum_values(): void
    {
        $this->assertSame('pending', BackupStatus::PENDING->value);
        $this->assertSame('running', BackupStatus::RUNNING->value);
        $this->assertSame('success', BackupStatus::SUCCESS->value);
        $this->assertSame('failed', BackupStatus::FAILED->value);
        $this->assertSame('expired', BackupStatus::EXPIRED->value);
    }

    public function test_command_type_enum_values(): void
    {
        $this->assertSame('run_backup', CommandType::RUN_BACKUP->value);
        $this->assertSame('run_wp_updates', CommandType::RUN_WP_UPDATES->value);
        $this->assertSame('rollback_restore', CommandType::ROLLBACK_RESTORE->value);
        $this->assertSame('run_malware_scan', CommandType::RUN_MALWARE_SCAN->value);
    }

    public function test_command_status_flow(): void
    {
        $this->assertSame('pending', CommandStatus::PENDING->value);
        $this->assertSame('sent', CommandStatus::SENT->value);
        $this->assertSame('executing', CommandStatus::EXECUTING->value);
        $this->assertSame('success', CommandStatus::SUCCESS->value);
        $this->assertSame('failed', CommandStatus::FAILED->value);
    }
}
