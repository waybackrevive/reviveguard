<?php

namespace Tests\Unit\Services;

use App\Services\UpdateSafetyService;
use Tests\TestCase;

class UpdateSafetyServiceTest extends TestCase
{
    /** @test */
    public function service_is_instantiable(): void
    {
        $this->assertInstanceOf(UpdateSafetyService::class, app(UpdateSafetyService::class));
    }
}
